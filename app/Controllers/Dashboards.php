<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

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
            // larang user biasa
            return redirect()->to('/dashboard')->send();
        }
        return null;
    }

    /** LIST */
    public function index()
    {
        if ($r = $this->mustAdmin()) return $r;

        $dashboards = $this->db->table('dashboards')->orderBy('name')->get()->getResultArray();

        // ambil users yang ter-assign per dashboard
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

        // hanya user role 'user' yang bisa di-assign
        $users = $this->db->table('users')
            ->select('id, username, full_name')
            ->where('role', 'user')
            ->where('is_active', 1)
            ->orderBy('username')
            ->get()->getResultArray();

        return view('layout/main', [
            'title'   => 'Add Dashboard',
            'content' => view('dashboards/form', [
                'action' => '/dashboards',
                'method' => 'post',
                'data'   => ['name' => ''],
                'users'  => $users,
                'selected' => [],
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

        $this->db->table('dashboards')->insert([
            'name'       => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $dashId = (int)$this->db->insertID();

        // simpan assignments
        $this->syncAssignments($dashId, $sel);

        return redirect()->to('/dashboards');
    }

    /** EDIT form */
    public function edit($id)
    {
        if ($r = $this->mustAdmin()) return $r;

        $dash = $this->db->table('dashboards')->where('id', (int)$id)->get()->getRowArray();
        if (!$dash) return redirect()->to('/dashboards');

        $users = $this->db->table('users')
            ->select('id, username, full_name')
            ->where('role', 'user')
            ->where('is_active', 1)
            ->orderBy('username')
            ->get()->getResultArray();

        $selected = $this->db->table('user_dashboards')
            ->select('user_id')
            ->where('dashboard_id', (int)$id)
            ->get()->getResultArray();
        $selected = array_map('intval', array_column($selected, 'user_id'));

        return view('layout/main', [
            'title'   => 'Edit Dashboard',
            'content' => view('dashboards/form', [
                'action'   => '/dashboards/' . (int)$id,
                'method'   => 'post',
                'data'     => $dash,
                'users'    => $users,
                'selected' => $selected,
            ]),
        ]);
    }

    /** UPDATE */
    public function update($id)
    {
        if ($r = $this->mustAdmin()) return $r;

        $id   = (int) $id;
        $name = trim((string) $this->request->getPost('name'));
        $sel  = (array)($this->request->getPost('user_ids') ?? []);

        if ($name === '') {
            return redirect()->back()->with('error','Nama wajib diisi');
        }

        $this->db->table('dashboards')->where('id', $id)->update([
            'name'       => $name,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->syncAssignments($id, $sel);

        return redirect()->to('/dashboards');
    }

    /** DELETE */
    public function delete($id)
    {
        if ($r = $this->mustAdmin()) return $r;

        $id = (int)$id;

        // hapus mapping & assignments dulu (FK-safe)
        $this->db->table('dashboard_monitors')->where('dashboard_id', $id)->delete();
        $this->db->table('user_dashboards')->where('dashboard_id', $id)->delete();
        $this->db->table('dashboards')->where('id', $id)->delete();

        return redirect()->to('/dashboards');
    }

    /** optional: lihat isi dashboard (pakai list mapping) */
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
            $uid = (int)$uid;
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
