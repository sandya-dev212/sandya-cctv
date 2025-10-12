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
        if (!session('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $q       = trim((string) ($this->request->getGet('q') ?? ''));
        $perPage = (int) ($this->request->getGet('per') ?? 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $role   = (string) (session('role') ?? 'user');
        // Fallback penting: beberapa flow login simpan "id", bukan "user_id"
        $userId = (int) (session('user_id') ?? session('id') ?? 0);

        $cards = [];
        $cli   = new Shinobi();

        if (in_array($role, ['superadmin', 'admin'], true)) {
            // ========= ADMIN/SUPERADMIN: agregat SEMUA monitor dari SEMUA NVR =========
            $nvrs = $this->db->table('nvrs')
                ->where('is_active', 1)
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();

            foreach ($nvrs as $n) {
                $resp = $cli->getMonitors($n['base_url'], $n['api_key'], $n['group_key'], null);
                if (!$resp['ok'] || !is_array($resp['data'])) {
                    continue;
                }
                foreach ($cli->normalizeMonitors($resp['data']) as $m) {
                    $alias = $m['name'] ?: ($n['name'] . ' / ' . $m['mid']);

                    if ($q !== '') {
                        $hay = mb_strtolower($alias . ' ' . $n['name'] . ' ' . $m['mid']);
                        if (strpos($hay, mb_strtolower($q)) === false) continue;
                    }

                    $cards[] = [
                        // tidak ada ID mapping → pakai composite supaya drag-order tetap unik
                        'id'         => 'NVR:' . $n['id'] . '/' . $m['mid'],
                        'alias'      => $alias,
                        'nvr'        => $n['name'],
                        'monitor_id' => (string) $m['mid'],
                        'hls'        => $cli->hlsUrl($n['base_url'], $n['api_key'], $n['group_key'], (string) $m['mid']),
                    ];
                }
            }
        } else {
            // ========= USER BIASA: hanya dashboard yang diassign ke user =========
            if ($userId > 0) {
                // Ambil daftar dashboard yang diassign ke user (tanpa JOIN, lebih aman)
                $dashRows = $this->db->table('user_dashboards')
                    ->select('dashboard_id')
                    ->where('user_id', $userId)
                    ->get()->getResultArray();

                $dashIds = array_map(fn($r) => (int)$r['dashboard_id'], $dashRows);
            } else {
                $dashIds = [];
            }

            if ($dashIds) {
                // Ambil mapping kamera untuk dashboard2 tsb
                $rows = $this->db->table('dashboard_monitors dm')
                    ->select('dm.id, dm.dashboard_id, dm.nvr_id, dm.monitor_id, dm.alias, dm.sort_order,
                              n.name AS nvr_name, n.base_url, n.api_key, n.group_key')
                    ->join('nvrs n', 'n.id = dm.nvr_id', 'inner')
                    ->whereIn('dm.dashboard_id', $dashIds)
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
                        'monitor_id' => (string) $r['monitor_id'],
                        'hls'        => $cli->hlsUrl($r['base_url'], $r['api_key'], $r['group_key'], (string) $r['monitor_id']),
                    ];
                }
            }
        }

        // pagination sederhana
        $total  = count($cards);
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;
        $paged  = array_slice($cards, $offset, $perPage);

        return view('layout/main', [
            'title'   => 'Dashboard',
            'content' => view('dashboard/index', [
                // View kamu pakai key "tiles"
                'tiles' => $paged,
                'total' => $total,
                'pages' => max(1, (int)ceil($total / $perPage)),
                'page'  => $page,
                'per'   => $perPage,
                'q'     => $q,
            ]),
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
