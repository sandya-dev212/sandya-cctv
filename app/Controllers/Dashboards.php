<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;
use App\Models\NvrModel;
use App\Models\DashboardModel;
use App\Models\DashboardMonitorModel;
use App\Libraries\Shinobi;

class Dashboards extends BaseController
{
    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    protected function mustAdmin()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login')->send();
        $role = (string)(session('role') ?? 'user');
        if (!in_array($role, ['admin','superadmin'], true)) {
            return redirect()->to('/dashboard')->send();
        }
        return null;
    }

    /** LIST */
    public function index()
    {
        if ($r = $this->mustAdmin()) return $r;

        $dashboards = $this->db->table('dashboards')->orderBy('name')->get()->getResultArray();

        $assigns = [];
        if ($dashboards) {
            $ids = array_column($dashboards, 'id');
            $rows = $this->db->table('user_dashboards ud')
                ->select('ud.dashboard_id, u.username')
                ->join('users u', 'u.id = ud.user_id', 'inner')
                ->whereIn('ud.dashboard_id', $ids)
                ->orderBy('u.username')
                ->get()->getResultArray();
                foreach ($rows as $r) {
                    $assigns[$r['dashboard_id']][] = $r['username'];
                }
        }

        return view('layout/main', [
            'title'   => 'Dashboards',
            'content' => view('dashboards/index', [
                'items'   => $dashboards,
                'assigns' => $assigns,
            ]),
        ]);
    }

    /** CREATE form */
    public function create()
    {
        if ($r = $this->mustAdmin()) return $r;

        $nvrModel = new NvrModel();
        $nvrs     = $nvrModel->where('is_active', 1)->orderBy('name')->findAll();

        $nvrId  = (int)($this->request->getGet('nvr_id') ?? ($nvrs[0]['id'] ?? 0));
        $active = $nvrId ? $nvrModel->find($nvrId) : null;

        $dModel = new DashboardModel();
        $dashboards = $dModel->orderBy('name')->findAll();
        $dashboardActiveId = (int)($this->request->getGet('dashboard_id') ?? ($dashboards[0]['id'] ?? 0));

        $dashboardMonitor = new DashboardMonitorModel();

        // Ambil monitors dari Shinobi
        $streams = ['ok'=>false, 'items'=>[],'msg'=>''];
        if ($active) {
            $cli = new Shinobi();
            $res = $cli->getMonitors($active['base_url'], $active['api_key'], $active['group_key']);
            if ($res['ok'] && is_array($res['data'])) {
                $items = $cli->normalizeMonitors($res['data']);
                $streams = ['ok'=>true, 'items'=>$items, 'msg'=>''];
            } else {
                $streams = ['ok'=>false, 'items'=>[], 'msg'=>('Shinobi error: ' . ($res['error'] ?? ('HTTP '.$res['code'])))];
            }
        }

        $users = $this->db->table('users')
            ->select('id, username, full_name, role')
            ->where('is_active', 1)
            ->orderBy('username')
            ->get()->getResultArray();

        return view('layout/main', [
            'title'   => 'Add Dashboard',
            'content' => view('dashboards/form', [
                'action' => '/dashboards',
                'method' => 'post',
                'data'   => ['name' => '', 'id' => 0],
                'users'  => $users,
                'selected' => [],
                'nvrs'              => $nvrs,
                'nvrActive'         => $active,
                'streams'           => $streams,
                'dashboards'        => $dashboards,
                'dashboardActiveId' => $dashboardActiveId,
                'assigned'          => [],
            ]),
        ]);
    }

    /** STORE */
    public function store()
    {
        if ($r = $this->mustAdmin()) return $r;

        $name = trim((string) $this->request->getPost('name'));
        $sel  = (array)($this->request->getPost('user_ids') ?? []);

        if ($name === '') {
            return redirect()->back()->with('error','Nama wajib diisi');
        }

        // ENFORCE: user role 'user' maksimal 1 dashboard
        if ($sel) {
            $users = $this->db->table('users')->select('id, role')->whereIn('id', $sel)->get()->getResultArray();
            $userIds = array_map(fn($r)=>(int)$r['id'], array_filter($users, fn($r)=>($r['role'] ?? 'user') === 'user'));
            if ($userIds) {
                $counts = $this->db->table('user_dashboards')
                    ->select('user_id, COUNT(*) AS c')
                    ->whereIn('user_id', $userIds)
                    ->groupBy('user_id')
                    ->get()->getResultArray();
                foreach ($counts as $row) {
                    if ((int)$row['c'] >= 1) {
                        return redirect()->back()->with('error', 'Setiap user biasa hanya boleh 1 dashboard. User ID: '.$row['user_id']);
                    }
                }
            }
        }

        $this->db->table('dashboards')->insert([
            'name'       => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $dashId = (int)$this->db->insertID();

        $this->syncAssignments($dashId, $sel);

        return redirect()->to('/dashboards/' . $dashId . '/edit');
    }

    /** EDIT form */
    public function edit($id)
    {
        if ($r = $this->mustAdmin()) return $r;

        $nvrModel = new NvrModel();
        $nvrs     = $nvrModel->where('is_active', 1)->orderBy('name')->findAll();

        $nvrId  = (int)($this->request->getGet('nvr_id') ?? ($nvrs[0]['id'] ?? 0));
        $active = $nvrId ? $nvrModel->find($nvrId) : null;

        $dModel = new DashboardModel();
        $dashboards = $dModel->orderBy('name')->findAll();
        $dashboardActiveId = (int)($this->request->getGet('dashboard_id') ?? ($dashboards[0]['id'] ?? 0));

        $dashboardMonitor = new DashboardMonitorModel();

        // Ambil monitors dari Shinobi
        $streams = ['ok'=>false, 'items'=>[],'msg'=>''];
        if ($active) {
            $cli = new Shinobi();
            $res = $cli->getMonitors($active['base_url'], $active['api_key'], $active['group_key']);
            if ($res['ok'] && is_array($res['data'])) {
                $items = $cli->normalizeMonitors($res['data']);
                $streams = ['ok'=>true, 'items'=>$items, 'msg'=>''];
            } else {
                $streams = ['ok'=>false, 'items'=>[], 'msg'=>('Shinobi error: ' . ($res['error'] ?? ('HTTP '.$res['code'])))];
            }
        }

        $dash = $this->db->table('dashboards')->where('id', (int)$id)->get()->getRowArray();
        if (!$dash) return redirect()->to('/dashboards');

        $users = $this->db->table('users')
            ->select('id, username, full_name, role')
            ->where('is_active', 1)
            ->orderBy('username')
            ->get()->getResultArray();

        $selected = $this->db->table('user_dashboards')
            ->select('user_id')
            ->where('dashboard_id', (int)$id)
            ->get()->getResultArray();

        $selected = array_map('intval', array_column($selected, 'user_id'));

        // buat set ID mapping yg sudah assigned untuk dashboard terpilih
        $assigned = [];
        if (!empty($streams['items']) && !empty($dashboards)) {

            $dashId = $dashboards[0]['id'] ?? 0;

            $assigned = $dashboardMonitor->select('id, monitor_id')
            ->where('dashboard_id', $id)
            ->where('nvr_id', $active['id'] ?? 0)
            ->get()->getResultArray();
        }

        return view('layout/main', [
            'title'   => 'Edit Dashboard',
            'content' => view('dashboards/form', [
                'action'   => '/dashboards/' . (int)$id,
                'method'   => 'post',
                'data'     => $dash,
                'users'    => $users,
                'selected' => $selected,
                'nvrs'              => $nvrs,
                'nvrActive'         => $active,
                'streams'           => $streams,
                'dashboards'        => $dashboards,
                'dashboardActiveId' => $dashboardActiveId,
                'assigned'          => $assigned,
            ]),
        ]);
    }

    /** UPDATE */
    public function update($id)
    {
        if ($r = $this->mustAdmin()) return $r;

        $name = trim($this->request->getPost('name'));
        $user_id  = $this->request->getPost('user_id');

        if ($name === '') {
            return redirect()->back()->with('error','Nama wajib diisi');
        }

        $this->db->table('dashboards')->where('id', $id)->update([
            'name'       => $name,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($user_id != "") {
            $this->db->table('user_dashboards')->insert([
                "user_id" => $user_id,
                "dashboard_id" => $id
            ]);
        }

        return redirect()->back()->with('success', 'Sukses tambah user');
    }

    /** DELETE */
    public function delete($id)
    {
        if ($r = $this->mustAdmin()) return $r;

        $id = (int)$id;

        $this->db->table('dashboard_monitors')->where('dashboard_id', $id)->delete();
        $this->db->table('user_dashboards')->where('dashboard_id', $id)->delete();
        $this->db->table('dashboards')->where('id', $id)->delete();

        return redirect()->to('/dashboards');
    }

    public function deleteAccess($dashboard_id) {
        
        $user_id = $this->request->getPost('user_id');

        $this->db->table('user_dashboards')
            ->where('dashboard_id', $dashboard_id)
            ->where('user_id', $user_id)
            ->delete();

        return redirect()->back()->with('success', 'Sukses hapus user');
    }

    /** view isi dashboard (opsional) */
    public function view($id)
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');

        $dash = $this->db->table('dashboards')->where('id', (int)$id)->get()->getRowArray();
        if (!$dash) return redirect()->to('/dashboards');

        $rows = $this->db->table('dashboard_monitors dm')
            ->select('dm.*, n.name AS nvr_name')
            ->join('nvrs n', 'n.id = dm.nvr_id', 'inner')
            ->where('dm.dashboard_id', (int)$id)
            ->orderBy('dm.sort_order', 'ASC')
            ->get()->getResultArray();

        return view('layout/main', [
            'title'   => 'Dashboard: ' . $dash['name'],
            'content' => view('dashboards/view', ['dash' => $dash, 'rows' => $rows]),
        ]);
    }

    /** helper: sinkron assignment user_dashboards */
    protected function syncAssignments(int $dashboardId, array $userIds): void
    {
        $this->db->table('user_dashboards')->where('dashboard_id', $dashboardId)->delete();

        $inserts = [];
        foreach ($userIds as $uid) {
            if ($uid > 0) {
                $inserts[] = [
                    'user_id'      => $uid,
                    'dashboard_id' => $dashboardId,
                ];
            }
        }
        if ($inserts) {
            $this->db->table('user_dashboards')->insertBatch($inserts);
        }
    }
}
