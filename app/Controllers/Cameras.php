<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\NvrModel;
use App\Models\DashboardModel;
use App\Models\DashboardMonitorModel;
use App\Libraries\Shinobi;

class Cameras extends Controller
{
    private function guard()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');
        $role = session('role') ?? 'user';
        if ($role === 'user') return redirect()->to('/dashboard');
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;

        $nvrModel = new NvrModel();
        $nvrs     = $nvrModel->where('is_active', 1)->orderBy('name')->findAll();

        $nvrId  = (int)($this->request->getGet('nvr_id') ?? ($nvrs[0]['id'] ?? 0));
        $active = $nvrId ? $nvrModel->find($nvrId) : null;

        $dModel = new DashboardModel();
        $dashboards = $dModel->orderBy('name')->findAll();
        $dashboardActiveId = (int)($this->request->getGet('dashboard_id') ?? ($dashboards[0]['id'] ?? 0));

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

        // Set of assigned monitor_id untuk dashboard aktif
        $assignedSet = [];
        if ($dashboardActiveId > 0) {
            $dm = new DashboardMonitorModel();
            $rows = $dm->select('monitor_id')->where([
                'dashboard_id' => $dashboardActiveId,
                'nvr_id'       => $nvrId,
            ])->findAll();
            foreach ($rows as $r) $assignedSet[(string)$r['monitor_id']] = true;
        }

        return view('layout/main', [
            'title'   => 'Cameras',
            'content' => view('cameras/index', [
                'nvrs'              => $nvrs,
                'nvrActive'         => $active,
                'streams'           => $streams,
                'dashboards'        => $dashboards,
                'dashboardActiveId' => $dashboardActiveId,
                'assignedSet'       => $assignedSet,
            ]),
        ]);
    }

    public function assign()
    {
        if ($r = $this->guard()) return $r;

        $dashboard_id = (int)$this->request->getPost('dashboard_id');
        $nvr_id       = (int)$this->request->getPost('nvr_id');
        $monitor_id   = trim($this->request->getPost('monitor_id') ?? '');
        $alias        = trim($this->request->getPost('alias') ?? '');

        if (!$dashboard_id || !$nvr_id || $monitor_id==='') {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Data kurang']);
        }

        $dm = new DashboardMonitorModel();
        $exists = $dm->where(['dashboard_id'=>$dashboard_id,'nvr_id'=>$nvr_id,'monitor_id'=>$monitor_id])->first();

        if ($exists) {
            $dm->update($exists['id'], ['alias'=>$alias]);
            return $this->response->setJSON(['ok'=>true,'msg'=>'Updated alias','id'=>$exists['id']]);
        } else {
            $sort = (int)($dm->where('dashboard_id',$dashboard_id)->selectMax('sort_order')->first()['sort_order'] ?? 0) + 1;
            $id = $dm->insert([
                'dashboard_id'=>$dashboard_id,
                'nvr_id'=>$nvr_id,
                'monitor_id'=>$monitor_id,
                'alias'=>$alias,
                'sort_order'=>$sort
            ]);
            return $this->response->setJSON(['ok'=>true,'msg'=>'Assigned','id'=>$id]);
        }
    }

    public function unassign()
    {
        if ($r = $this->guard()) return $r;
        $id = (int)$this->request->getPost('dashboard_monitor_id');
        if (!$id) return $this->response->setJSON(['ok'=>false,'msg'=>'id kosong']);
        (new DashboardMonitorModel())->delete($id);
        return $this->response->setJSON(['ok'=>true,'msg'=>'Removed']);
    }

    public function mappings()
    {
        if ($r = $this->guard()) return $r;

        $dModel = new DashboardModel();
        $dashboards = $dModel->orderBy('name')->findAll();

        $dashboardId = (int)(
            $this->request->getGet('dashboard_id')
            ?? $this->request->getGet('dashbboard_id')
            ?? ($dashboards[0]['id'] ?? 0)
        );

        $rows = [];
        if ($dashboardId) {
            $dm = new DashboardMonitorModel();
            $rows = $dm->where('dashboard_id', $dashboardId)
                ->orderBy('sort_order', 'asc')
                ->findAll();
        }

        return view('layout/main', [
            'title'   => 'Camera Mappings',
            'content' => view('cameras/mappings', [
                'dashboards'  => $dashboards,
                'dashboardId' => $dashboardId,
                'rows'        => $rows,
            ]),
        ]);
    }

    public function updateMapping()
    {
        if ($r = $this->guard()) return $r;

        $id    = (int)$this->request->getPost('id');
        $alias = trim((string)$this->request->getPost('alias'));
        $sort  = (int)$this->request->getPost('sort_order');

        if (!$id) return $this->response->setJSON(['ok'=>false,'msg'=>'id kosong']);

        $data = ['alias'=>$alias, 'sort_order'=>$sort];
        (new DashboardMonitorModel())->update($id, $data);
        return $this->response->setJSON(['ok'=>true]);
    }

    public function deleteMapping()
    {
        if ($r = $this->guard()) return $r;

        $id = (int)$this->request->getPost('id');
        if (!$id) return $this->response->setJSON(['ok'=>false,'msg'=>'id kosong']);

        (new DashboardMonitorModel())->delete($id);
        return $this->response->setJSON(['ok'=>true]);
    }
}
