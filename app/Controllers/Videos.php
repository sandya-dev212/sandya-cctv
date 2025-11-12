<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NvrModel;
use App\Libraries\Shinobi;
use Config\Database;

class Videos extends BaseController
{
    public function index()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');

        $db     = Database::connect();
        $role   = session('role') ?? 'user';
        $userId = (int)(session('user_id') ?? 0);

        $nvrs = (new NvrModel())
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $nvrId = (int)($this->request->getGet('nvr_id') ?? 0);
        $mon   = trim((string)($this->request->getGet('mon') ?? ''));
        $monitors = [];

        if ($nvrId && $mon !== '') {
            $nvr = (new NvrModel())->find($nvrId);
            if ($nvr) {
                $cli = new Shinobi();
                $res = $cli->getMonitors($nvr['base_url'], $nvr['api_key'], $nvr['group_key']);
                if (($res['ok'] ?? false) && is_array($res['data'])) {
                    $monitors = $cli->normalizeMonitors($res['data']);

                    // ?? Filter kamera berdasar dashboard user
                    if ($role === 'user' && $userId > 0) {
                        $allowedIds = $this->getUserMonitorIds($db, $userId);
                        $monitors = array_values(array_filter($monitors, fn($m) => in_array($m['mid'], $allowedIds)));
                    }
                }
            }
        }

        return view('layout/main', [
            'title'   => 'Videos',
            'content' => view('videos/index', [
                'nvrs'     => $nvrs,
                'nvrId'    => $nvrId,
                'mon'      => $mon,
                'monitors' => $monitors,
            ]),
        ]);
    }

    public function monitors()
    {
        if (!session('isLoggedIn')) {
            return $this->response->setJSON(['ok' => false, 'items' => []]);
        }

        $db     = Database::connect();
        $role   = session('role') ?? 'user';
        $userId = (int)(session('user_id') ?? 0);

        $nvrId = (int)$this->request->getGet('nvr_id');
        if (!$nvrId) return $this->response->setJSON(['ok' => true, 'items' => []]);

        $nvr = (new NvrModel())->find($nvrId);
        if (!$nvr) return $this->response->setJSON(['ok' => true, 'items' => []]);

        $cli = new Shinobi();
        $res = $cli->getMonitors($nvr['base_url'], $nvr['api_key'], $nvr['group_key']);
        $items = ($res['ok'] ?? false) ? $cli->normalizeMonitors($res['data']) : [];

        if ($role === 'user' && $userId > 0) {
            $allowedIds = $this->getUserMonitorIds($db, $userId);
            $items = array_values(array_filter($items, fn($m) => in_array($m['mid'], $allowedIds)));
        }

        return $this->response->setJSON(['ok' => true, 'items' => $items]);
    }

    public function data()
    {
        if (!session('isLoggedIn')) {
            return $this->response->setJSON(['ok' => false, 'error' => 'unauthorized']);
        }

        $db     = Database::connect();
        $role   = session('role') ?? 'user';
        $userId = (int)(session('user_id') ?? 0);

        $nvrId = (int)$this->request->getGet('nvr_id');
        $mon   = trim((string)$this->request->getGet('mon'));
        $start = (int)$this->request->getGet('start');
        $end   = (int)$this->request->getGet('end');

        if (!$nvrId || $mon === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'missing params']);
        }

        // ?? Cegah user akses kamera yg bukan miliknya
        if ($role === 'user' && $userId > 0) {
            $allowedIds = $this->getUserMonitorIds($db, $userId);
            if (!in_array($mon, $allowedIds)) {
                return $this->response->setJSON(['ok' => false, 'error' => 'unauthorized camera']);
            }
        }

        $nvr = (new NvrModel())->find($nvrId);
        if (!$nvr) {
            return $this->response->setJSON(['ok' => false, 'error' => 'nvr not found']);
        }

        $iso = 'Y-m-d\TH:i:s';
        $startIso = date($iso, (int)floor($start / 1000));
        $endIso   = date($iso, (int)floor($end / 1000));

        $cli = new Shinobi();
        $res = $cli->getVideosRange(
            $nvr['base_url'],
            $nvr['api_key'],
            $nvr['group_key'],
            $mon,
            $startIso,
            $endIso,
            null,
            null,
            false
        );

        if (!($res['ok'] ?? false) || !is_array($res['data'])) {
            return $this->response->setJSON(['ok' => false, 'status' => $res['code'] ?? 500, 'data' => []]);
        }

        $payload = $res['data'];
        $list = $payload['videos'] ?? $payload;
        if (!is_array($list)) $list = [];

        $base = rtrim($nvr['base_url'], '/');
        $rows = [];

        foreach ($list as $v) {
            
            $timeObj = new \DateTime($v['time']);
            $timeObj->setTimezone(new \DateTimeZone('Asia/Jakarta'));

            $href = (string)($v['href'] ?? '');
            if ($href === '') continue;

            $filename = (string)($v['filename'] ?? basename($href));
            $size     = (int)($v['size'] ?? 0);
            $full     = $base . $href;

            $rows[] = [
                'name'     => $filename,
                'time'     => $timeObj->format('Y-m-d H:i:s'),
                'size'     => number_format(($size / 1024 / 1024), 2),
                'play'     => $full,
                'download' => $full,
            ];
        }

        usort($rows, fn($a, $b) => strcmp($b['name'], $a['name']));

        return $this->response->setJSON(['ok' => true, 'data' => $rows]);
    }

    /**
     * Ambil semua monitor_id yang dimiliki user dari relasi dashboard
     */
    private function getUserMonitorIds($db, int $userId): array
    {
        $rows = $db->query("
            SELECT dm.monitor_id
            FROM user_dashboards ud
            JOIN dashboard_monitors dm ON dm.dashboard_id = ud.dashboard_id
            WHERE ud.user_id = ?
        ", [$userId])->getResultArray();

        return array_column($rows, 'monitor_id');
    }
}
