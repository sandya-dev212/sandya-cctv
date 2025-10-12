<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;
use App\Libraries\Shinobi;

/**
 * Debug controller — only for temporary diagnostics.
 * Access: /debug/dashboard_check
 */
class Debug extends BaseController
{
    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function dashboard_check()
    {
        // simple auth check: hanya superadmin atau local admin boleh buka (optional)
        // comment out next 3 lines if you cannot login right now
        $role = session('role') ?? '(none)';
        if (!in_array($role, ['superadmin','admin','user'], true)) {
            echo "<pre>Warning: session role missing or not set. Please login first.</pre>";
        }

        echo "<h2>Debug: Dashboard mapping check</h2>";
        echo "<style>body{font-family:system-ui,Segoe UI,Arial} pre{background:#111;color:#dfe6ef;padding:12px;border-radius:6px}</style>";

        // 1) show session keys relevant
        echo "<h3>Session (relevant keys)</h3><pre>";
        $keys = ['isLoggedIn','user_id','id','username','role','name'];
        $s = [];
        foreach ($keys as $k) {
            $s[$k] = session($k);
        }
        echo htmlspecialchars(print_r($s, true));
        echo "</pre>";

        // 2) show which user id we'll use
        $userId = (int) (session('user_id') ?? session('id') ?? 0);
        echo "<h3>Resolved user id used for lookup</h3><pre>" . htmlspecialchars($userId) . "</pre>";

        // 3) show list of users table rows for this id (sanity)
        echo "<h3>users table (matching this id)</h3><pre>";
        $u = $this->db->table('users')->where('id', $userId)->get()->getResultArray();
        echo htmlspecialchars(print_r($u, true));
        echo "</pre>";

        // 4) list user_dashboards for this user
        echo "<h3>user_dashboards rows for this user</h3><pre>";
        $udRows = $this->db->table('user_dashboards')->where('user_id', $userId)->get()->getResultArray();
        echo "count: " . count($udRows) . "\n";
        echo htmlspecialchars(print_r($udRows, true));
        echo "</pre>";

        // 5) list dashboard_monitors for those dashboards (if any)
        $dashIds = array_map(fn($r) => (int)$r['dashboard_id'], $udRows);
        echo "<h3>dashboard_monitors for dashboard_ids: " . htmlspecialchars(json_encode($dashIds)) . "</h3>";
        if (empty($dashIds)) {
            echo "<pre>no dashboard assignments found for this user (user_dashboards empty)</pre>";
        } else {
            $rows = $this->db->table('dashboard_monitors as dm')
                ->select('dm.id, dm.dashboard_id, dm.nvr_id, dm.monitor_id, dm.alias, dm.sort_order, dm.created_at, n.name AS nvr_name')
                ->join('nvrs n', 'n.id = dm.nvr_id', 'left')
                ->whereIn('dm.dashboard_id', $dashIds)
                ->orderBy('dm.sort_order','asc')
                ->get()->getResultArray();
            echo "<pre>";
            echo "count: " . count($rows) . "\n";
            echo htmlspecialchars(print_r($rows, true));
            echo "</pre>";
        }

        // 6) If user is superadmin/admin, show what "all" mapping would be (from dashboard_monitors)
        echo "<h3>Global dashboard_monitors table head (first 30 rows)</h3><pre>";
        $all = $this->db->table('dashboard_monitors as dm')
            ->select('dm.id, dm.dashboard_id, dm.nvr_id, dm.monitor_id, dm.alias, dm.sort_order, n.name AS nvr_name')
            ->join('nvrs n', 'n.id = dm.nvr_id', 'left')
            ->orderBy('dm.id','asc')
            ->limit(30)
            ->get()->getResultArray();
        echo "count (first 30): " . count($all) . "\n";
        echo htmlspecialchars(print_r($all, true));
        echo "</pre>";

        // 7) List NVRS (active) so we can confirm base_url/api keys exist
        echo "<h3>NVRS (active)</h3><pre>";
        $nvrs = $this->db->table('nvrs')->where('is_active',1)->orderBy('id','asc')->get()->getResultArray();
        echo "count active nvrs: " . count($nvrs) . "\n";
        // Hide api_key in output for safety; show only first/last 4 chars as hint
        foreach ($nvrs as &$n) {
            $ak = $n['api_key'] ?? '';
            $n['api_key_hint'] = $ak ? substr($ak,0,4) . '...' . substr($ak,-4) : '(empty)';
            unset($n['api_key']);
        }
        echo htmlspecialchars(print_r($nvrs, true));
        echo "</pre>";

        // 8) If we have at least one mapping row from step 5, test Shinobi URL builders (and optionally a GET)
        echo "<h3>HLS/ JPEG url builder test (for first 3 mappings)</h3><pre>";
        $cli = new Shinobi();
        $testRows = [];
        if (!empty($rows)) {
            $testRows = array_slice($rows, 0, 3);
        } elseif (!empty($all)) {
            $testRows = array_slice($all, 0, 3);
        }
        if (empty($testRows)) {
            echo "No rows to build URLs from (no mappings present).\n";
        } else {
            foreach ($testRows as $r) {
                // find referenced nvr row
                $nvrRow = $this->db->table('nvrs')->where('id', (int)$r['nvr_id'])->get()->getRowArray();
                $hls = '(nvr missing)';
                $jpeg = '(nvr missing)';
                if ($nvrRow) {
                    $hls = $cli->hlsUrl($nvrRow['base_url'] ?? '', $nvrRow['api_key'] ?? '', $nvrRow['group_key'] ?? '', (string)$r['monitor_id']);
                    $jpeg = $cli->jpegUrl($nvrRow['base_url'] ?? '', $nvrRow['api_key'] ?? '', $nvrRow['group_key'] ?? '', (string)$r['monitor_id']);
                }
                echo "mapping id={$r['id']} dashboard={$r['dashboard_id']} nvr_id={$r['nvr_id']} monitor={$r['monitor_id']}\n";
                echo "  nvr_name: " . ($r['nvr_name'] ?? '(none)') . "\n";
                echo "  hls:  " . $hls . "\n";
                echo "  jpeg: " . $jpeg . "\n\n";
            }
        }
        echo "</pre>";

        // 9) Quick integrity checks
        echo "<h3>Integrity checks</h3><pre>";
        // Are there user_dashboards rows where dashboard_id doesn't exist?
        $bad = $this->db->query("
            SELECT ud.* FROM user_dashboards ud
            LEFT JOIN dashboards d ON d.id = ud.dashboard_id
            WHERE d.id IS NULL
            LIMIT 20
        ")->getResultArray();
        echo "user_dashboards pointing to missing dashboards (max 20): " . count($bad) . "\n";
        echo htmlspecialchars(print_r($bad, true)) . "\n";

        // Are there dashboard_monitors where nvr_id missing?
        $bad2 = $this->db->query("
            SELECT dm.* FROM dashboard_monitors dm
            LEFT JOIN nvrs n ON n.id = dm.nvr_id
            WHERE n.id IS NULL
            LIMIT 20
        ")->getResultArray();
        echo "dashboard_monitors pointing to missing nvrs (max 20): " . count($bad2) . "\n";
        echo htmlspecialchars(print_r($bad2, true)) . "\n";

        echo "</pre>";

        echo "<h3>What to send back here</h3>";
        echo "<p>Paste the full HTML output (or take screenshots) and especially these items:</p>";
        echo "<ul>
                <li>Session keys block</li>
                <li>user_dashboards rows</li>
                <li>dashboard_monitors rows for those dashboards</li>
                <li>NVRS list (active)</li>
              </ul>";
        echo "<p>I'll analyze and point the exact mismatch (common causes: session user_id mismatch, user_dashboards points to wrong dashboard_id, or dashboard_monitors NVR id doesn't exist).</p>";
    }
}
