<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;
use App\Libraries\Shinobi;

class Dashboard extends BaseController
{
    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function index()
    {

        $id = $this->request->getGet('id') ?? 0;

        if (!session('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $role   = (string) (session('role') ?? 'user');
        $userId = (int) (session('user_id') ?? session('id') ?? 0);
        $dashAccess = [];

        // Check is the user has the access to the dashboard or not
        $getUserDash = $this->db->table('user_dashboards')
            ->where('user_id', $userId)
            ->get()->getResultArray();

        $dashAccess = $this->db->table('user_dashboards')
            ->join('dashboards', 'user_dashboards.dashboard_id = dashboards.id')
            ->select('user_dashboards.dashboard_id as id, dashboards.name as name')
            ->where('user_id', $userId)
            ->get()->getResultArray();

        $dashIds = array_column($getUserDash, 'dashboard_id');

        // This code is to redirect the button "Dashboard" in Views/partials/navbar.php
        // Since the default href of the "Dashboard" button is 'dashboard?id=0'
        // So, when user click the dashboard button in navbar, the user will be redirected to theirs own dashboard
        // This code is not affected the super admin user
        if ($id == 0 && $role == 'user') {
            return redirect()->to('/dashboard?id=' . $dashAccess[0]['id']);
        }
        
        // This code is to prevent the user to opening the dashboard id that doesn't assigned to him by manually type the dashboard id in the URL path
        if ($role == 'user' && !in_array($id, $dashIds)) {
            session()->setFlashdata('message', 'Anda tidak memiliki akses ke dashboard dengan id ' . $id . '!');
            return redirect()->back();
        }

		$q       = trim((string) ($this->request->getGet('q') ?? ''));
		$perPage = (int) ($this->request->getGet('per') ?? 6);
		$perPage = in_array($perPage, [6, 12, 24, 46, 100], true) ? $perPage : 6;

        $cards = [];
        $cli   = new Shinobi();

        if (in_array($role, ['superadmin', 'admin'], true)) {
            $nvrs = $this->db->table('nvrs')
                ->where('is_active', 1)
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();

            foreach ($nvrs as $n) {
                $resp = $cli->getMonitors($n['base_url'], $n['api_key'], $n['group_key'], null);
                if (!$resp['ok'] || !is_array($resp['data'])) continue;

                foreach ($cli->normalizeMonitors($resp['data']) as $m) {
                    $alias = $m['name'] ?: ($n['name'] . ' / ' . $m['mid']);
                    if ($q !== '') {
                        $hay = mb_strtolower($alias . ' ' . $n['name'] . ' ' . $m['mid']);
                        if (strpos($hay, mb_strtolower($q)) === false) continue;
                    }
                    $cards[] = [
                        'id'         => 'NVR:' . $n['id'] . '/' . $m['mid'],
                        'alias'      => $alias,
                        'nvr'        => $n['name'],
                        'nvr_id'     => (int)$n['id'],
                        'monitor_id' => (string)$m['mid'],
                        'hls'        => $cli->hlsUrl($n['base_url'], $n['api_key'], $n['group_key'], (string)$m['mid']),
                        'size'       => [ 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 4]
                    ];
                }
            }
        } else {

            if ($dashAccess) {
                $rows = $this->db->table('dashboard_monitors dm')
                    ->select('dm.id, dm.dashboard_id, dm.nvr_id, dm.monitor_id, dm.alias, dm.sort_order,
                              n.name AS nvr_name, n.base_url, n.api_key, n.group_key')
                    ->join('nvrs n', 'n.id = dm.nvr_id', 'inner')
                    ->where('dm.dashboard_id', $id)
                    ->orderBy('dm.sort_order', 'ASC')
                    ->get()->getResultArray();

                foreach ($rows as $r) {
                    $alias = $r['alias'] ?: ($r['nvr_name'] . ' / ' . $r['monitor_id']);
                    if ($q !== '') {
                        $hay = mb_strtolower($alias . ' ' . $r['nvr_name'] . ' ' . $r['monitor_id']);
                        if (strpos($hay, mb_strtolower($q)) === false) continue;
                    }
                    $cards[] = [
                        'id'         => (string) $r['id'],
                        'alias'      => $alias,
                        'nvr'        => $r['nvr_name'],
                        'nvr_id'     => (int)$r['nvr_id'],
                        'monitor_id' => (string)$r['monitor_id'],
                        'hls'        => $cli->hlsUrl($r['base_url'], $r['api_key'], $r['group_key'], (string)$r['monitor_id']),
                        'size'       => [ 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 4]
                    ];
                }
            }
        }

        $total  = count($cards);
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;
        $paged  = array_slice($cards, $offset, $perPage);

        return view('layout/main', [
            'title'   => 'Dashboard',
            'content' => view('dashboard/index', [
                'tiles' => $paged,
                'total' => $total,
                'pages' => max(1, (int)ceil($total / $perPage)),
                'page'  => $page,
                'per'   => $perPage,
                'q'     => $q,
                'curDashId' => $id,
                'dashAccess' => $dashAccess
            ]),
        ]);
    }

    public function getAllCameras() {

        $cards = [];
        $cli   = new Shinobi();

        $nvrs = $this->db->table('nvrs')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        foreach ($nvrs as $n) {
            $resp = $cli->getMonitors($n['base_url'], $n['api_key'], $n['group_key'], null);

            if (!$resp['ok'] || !is_array($resp['data'])) continue;

            foreach ($cli->normalizeMonitors($resp['data']) as $m) {
                $alias = $m['name'] ?: ($n['name'] . ' / ' . $m['mid']);
            
                $cards[] = [
                    'id'         => 'NVR:' . $n['id'] . '/' . $m['mid'],
                    'alias'      => $alias,
                    'nvr'        => $n['name'],
                    'nvr_id'     => (int)$n['id'],
                    'monitor_id' => (string)$m['mid'],
                    'hls'        => $cli->hlsUrl($n['base_url'], $n['api_key'], $n['group_key'], (string)$m['mid']),
                    'size'       => [ 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 4]
                ];
            }
        }

        return $this->response->setJSON([
            'tiles' => $cards,
        ]);
    }

    public function refresh()
    {
        return $this->response->setJSON([
            'ok'    => true,
            'ts'    => date('c'),
            'items' => [],
        ]);
    }
}
