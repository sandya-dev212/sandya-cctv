<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Libraries\Ldap_lib;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class AccountLinks extends BaseController
{
    /** Guard superadmin */
    private function mustSuperadmin()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');
        if ((session('role') ?? '') !== 'superadmin') return redirect()->to('/dashboard');
        return null;
    }

    /** Helper DB */
    private function db()
    {
        return Database::connect();
    }

    /** Ambil semua child yang linked ke parent */
    private function getLinkedChildIds(int $parentId): array
    {
        $db   = $this->db();
        $rows = $db->table('user_links')->select('child_user_id')->where('parent_user_id', $parentId)->get()->getResultArray();
        return array_map(fn($r) => (int)$r['child_user_id'], $rows);
    }

    /** Cek apakah parent superadmin & link valid */
    private function isLinked(int $parentId, int $childId): bool
    {
        $db = $this->db();
        $row = $db->table('user_links')->where([
            'parent_user_id' => $parentId,
            'child_user_id'  => $childId,
        ])->get()->getFirstRow();
        return (bool)$row;
    }

    /** UI: Checklist link akun (dipanggil dari Users List) */
    public function linkUI($parentId = null)
    {
        if ($r = $this->mustSuperadmin()) return $r;

        $parentId = (int) $parentId;
        if ($parentId <= 0) return redirect()->to('/users');

        $um     = new UserModel();
        $parent = $um->find($parentId);
        if (!$parent) return redirect()->to('/users');

        // Hanya boleh link untuk parent superadmin
        if (($parent['role'] ?? 'user') !== 'superadmin') {
            return $this->response->setStatusCode(403)->setBody('Parent harus superadmin.');
        }

        // List semua user aktif (kecuali parent)
        $users = $um->select('*')->where('id !=', $parentId)->where('is_active', 1)->orderBy('username')->findAll();

        $linked = $this->getLinkedChildIds($parentId);
        $csrf   = csrf_hash();

        $html = $this->renderLinkUI($parent, $users, $linked, $csrf);
        return $this->response->setStatusCode(200)->setBody($html);
    }

    /** Render HTML untuk UI checklist */
    private function renderLinkUI(array $parent, array $users, array $linkedIds, string $csrf): string
    {
        $parentSafe = esc($parent['username']);
        ob_start(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Link Accounts – <?= $parentSafe ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{background:#0b1020;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial}
.wrap{max-width:960px;margin:40px auto;padding:20px}
.h{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.badge{background:#1f2937;border:1px solid #374151;border-radius:999px;padding:6px 12px;font-size:12px}
.card{background:#0f172a;border:1px solid #1f2937;border-radius:16px;padding:16px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.item{display:flex;align-items:center;gap:8px;padding:8px;border-radius:10px;background:#0b1220;border:1px solid #1f2937}
.actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
.btn{padding:10px 14px;border-radius:10px;border:1px solid #374151;background:#111827;color:#e5e7eb;text-decoration:none;cursor:pointer}
.btn.primary{background:#7c3aed;border-color:#7c3aed;color:#0b1020;font-weight:700}
.search{margin-bottom:12px}
.search input{width:100%;padding:10px;border-radius:10px;border:1px solid #374151;background:#0b1220;color:#e5e7eb}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <div class="h">
    <h2 style="margin:0">Link Accounts</h2>
    <span class="badge">Parent: <?= $parentSafe ?></span>
  </div>

  <form id="fLink" class="card" method="post" action="/users/link/save">
    <input type="hidden" name="<?= csrf_token() ?>" value="<?= $csrf ?>">
    <input type="hidden" name="parent_id" value="<?= (int)$parent['id'] ?>">

    <div class="search">
      <input type="text" id="q" placeholder="Cari user… (username / full name / email)">
    </div>

    <div class="grid" id="list">
      <?php foreach ($users as $u):
          $id   = (int)$u['id'];
          $nm   = esc($u['username']);
          $fn   = esc($u['full_name'] ?? '');
          $em   = esc($u['email'] ?? '');
          $rl   = esc($u['role'] ?? 'user');
          $auth = esc($u['auth_source'] ?? 'local');
          $chk  = in_array($id, $linkedIds, true) ? 'checked' : '';
      ?>
      <label class="item" data-text="<?= strtolower($u['username'].' '.$u['full_name'].' '.$u['email']) ?>">
        <input type="checkbox" name="child_ids[]" value="<?= $id ?>" <?= $chk ?>>
        <div>
          <div><strong><?= $nm ?></strong> <span style="opacity:.7">(#<?= $id ?>)</span></div>
          <div style="font-size:12px;opacity:.8"><?= $fn ?> · <?= $em ?> · role: <?= $rl ?> · auth: <?= strtoupper($auth) ?></div>
        </div>
      </label>
      <?php endforeach; ?>
    </div>

    <div class="actions">
      <a class="btn" href="/users">Batal</a>
      <button class="btn primary" type="submit">Simpan Link</button>
    </div>
  </form>
</div>

<script>
const q = document.getElementById('q');
const list = document.getElementById('list');
q?.addEventListener('input', () => {
  const s = q.value.toLowerCase();
  list.querySelectorAll('.item').forEach(it => {
    const t = it.getAttribute('data-text') || '';
    it.style.display = t.includes(s) ? '' : 'none';
  });
});
</script>
</body>
</html>
<?php
        return ob_get_clean();
    }

    /** Action: Simpan link (replace penuh: set baru = checklist) */
    public function saveLinks()
    {
        if ($r = $this->mustSuperadmin()) return $r;

        $parentId = (int) $this->request->getPost('parent_id');
        $childIds = $this->request->getPost('child_ids') ?? [];

        if ($parentId <= 0) return redirect()->to('/users')->with('error','Parent invalid.');
        if (!is_array($childIds)) $childIds = [];

        // Validasi parent harus superadmin
        $um     = new UserModel();
        $parent = $um->find($parentId);
        if (!$parent || ($parent['role'] ?? 'user') !== 'superadmin') {
            return redirect()->to('/users')->with('error','Parent bukan superadmin.');
        }

        // Bersihin self-link & duplikat
        $childIds = array_unique(array_filter(array_map('intval', $childIds), fn($id) => $id > 0 && $id !== $parentId));

        $db = $this->db();
        $db->transStart();

        // Hapus semua link existing parent → isi ulang
        $db->table('user_links')->where('parent_user_id', $parentId)->delete();

        // Insert baru
        foreach ($childIds as $cid) {
            // opsional: cegah child yang superadmin juga → masih boleh, tapi tidak switch ke parent tanpa password nanti
            $db->table('user_links')->insert([
                'parent_user_id' => $parentId,
                'child_user_id'  => $cid,
            ]);
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            return redirect()->to('/users')->with('error','Gagal menyimpan link.');
        }

        return redirect()->to('/users')->with('message','Link tersimpan.');
    }

    /** Popup untuk navbar: list linked users (superadmin) + form switch ke parent (kalau lagi di child) */
    public function switcherPopup()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');

        $userId = (int) session('user_id');
        $role   = session('role') ?? 'user';

        $um  = new UserModel();
        $me  = $um->find($userId);

        $db  = $this->db();

        $linkedUsers = [];
        $parentOfMe  = null;

        if ($role === 'superadmin') {
            // superadmin: ambil child linked
            $rows = $db->table('user_links ul')
                ->select('u.*')
                ->join('users u', 'u.id = ul.child_user_id')
                ->where('ul.parent_user_id', $userId)
                ->orderBy('u.username')
                ->get()->getResultArray();
            $linkedUsers = $rows;
        } else {
            // user biasa/admin: cek apakah dia child dari parent mana
            $row = $db->table('user_links ul')
                ->select('p.*')
                ->join('users p', 'p.id = ul.parent_user_id')
                ->where('ul.child_user_id', $userId)
                ->get()->getFirstRow('array');
            if ($row) $parentOfMe = $row;
        }

        $csrf = csrf_hash();
        $html = $this->renderSwitcher($me, $role, $linkedUsers, $parentOfMe, $csrf);
        return $this->response->setStatusCode(200)->setBody($html);
    }

    /** Render HTML popup floating (dipanggil via fetch dan disisipkan ke navbar) */
    private function renderSwitcher(array $me, string $role, array $linkedUsers, ?array $parent, string $csrf): string
    {
        $meU = esc($me['username'] ?? 'user');
        ob_start(); ?>
<div id="acc-switcher" style="position:fixed;top:70px;right:20px;z-index:9999;display:block">
  <div style="background:#0f172a;border:1px solid #1f2937;border-radius:16px;min-width:320px;max-width:420px;padding:14px;box-shadow:0 10px 30px rgba(0,0,0,.4)">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px">
      <div><strong>Signed in:</strong> <?= $meU ?> <span style="opacity:.6">[<?= esc($role) ?>]</span></div>
      <button onclick="document.getElementById('acc-switcher').remove()" style="background:#111827;border:1px solid #374151;border-radius:10px;color:#e5e7eb;padding:6px 10px;cursor:pointer">Close</button>
    </div>

    <?php if ($role === 'superadmin'): ?>
      <div style="font-size:12px;opacity:.8;margin:6px 0 8px">Linked users:</div>
      <?php if (empty($linkedUsers)): ?>
        <div style="opacity:.8">Belum ada linked user. Buka <code>/users</code> → “Link Accounts”.</div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($linkedUsers as $u):
              $id = (int)$u['id']; $uu = esc($u['username']); $nm = esc($u['full_name'] ?? '');
              $rl = esc($u['role'] ?? 'user'); $au = esc($u['auth_source'] ?? 'local'); ?>
            <form method="post" action="/switch-as/<?= $id ?>" onsubmit="return confirm('Switch ke <?= $uu ?>?')">
              <input type="hidden" name="<?= csrf_token() ?>" value="<?= $csrf ?>">
              <button type="submit" style="width:100%;text-align:left;padding:10px;border-radius:10px;border:1px solid #374151;background:#0b1220;color:#e5e7eb;cursor:pointer">
                <div><strong><?= $uu ?></strong> <span style="opacity:.7">[<?= $rl ?> · <?= strtoupper($au) ?>]</span></div>
                <?php if ($nm !== ''): ?><div style="font-size:12px;opacity:.8"><?= $nm ?></div><?php endif; ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <?php if ($parent): ?>
        <div style="opacity:.9;margin-bottom:8px">Switch ke parent:</div>
        <form method="post" action="/switch-to-parent" onsubmit="return confirm('Switch ke parent (<?= esc($parent['username']) ?>)?')">
          <input type="hidden" name="<?= csrf_token() ?>" value="<?= $csrf ?>">
          <input type="hidden" name="parent_username" value="<?= esc($parent['username']) ?>">
          <div style="display:flex;flex-direction:column;gap:8px">
            <input name="password" type="password" placeholder="Password parent" style="padding:10px;border-radius:10px;border:1px solid #374151;background:#0b1220;color:#e5e7eb" required>
            <button type="submit" style="padding:10px;border-radius:10px;border:1px solid #7c3aed;background:#7c3aed;color:#0b1020;font-weight:700;cursor:pointer">Switch to <?= esc($parent['username']) ?></button>
          </div>
        </form>
      <?php else: ?>
        <div style="opacity:.8">Tidak ada parent untuk akun ini.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<script>
// no-op: popup sudah siap
</script>
<?php
        return ob_get_clean();
    }

    /** Action: Superadmin → switch jadi child tanpa password */
    public function switchAs($childId = null)
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');
        $role = session('role') ?? 'user';
        if ($role !== 'superadmin') return redirect()->to('/dashboard');

        $parentId = (int) session('user_id');
        $childId  = (int) $childId;

        if ($childId <= 0) return redirect()->to('/dashboard');

        if (!$this->isLinked($parentId, $childId)) {
            return redirect()->to('/dashboard')->with('error','User tidak ter-link.');
        }

        $um    = new UserModel();
        $child = $um->find($childId);
        if (!$child || (int)$child['is_active'] !== 1) {
            return redirect()->to('/dashboard')->with('error','Child user tidak aktif.');
        }

        // Simpan jejak impersonator untuk bisa revert
        $payload = [
            'isLoggedIn'     => true,
            'user_id'        => (int) $child['id'],
            'id'             => (int) $child['id'],
            'uid'            => $child['username'],
            'username'       => $child['username'],
            'name'           => $child['full_name'] ?? $child['username'],
            'role'           => $child['role'] ?? 'user',
            'auth'           => $child['auth_source'] ?? 'local',
            'impersonator_id'=> $parentId, // penting
        ];
        session()->set($payload);

        // sentuh last_login_at child (opsional)
        $um->update($child['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/dashboard')->with('message','Switched as '.$child['username']);
    }

    /**
     * Action: Child → switch ke parent (WAJIB password parent)
     * - Autentikasi parent via local (password_verify) atau LDAP (Ldap_lib)
     */
    public function switchToParent()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');

        $parentUsername = trim((string)$this->request->getPost('parent_username'));
        $password       = (string)$this->request->getPost('password');
        if ($parentUsername === '' || $password === '') {
            return redirect()->to('/dashboard')->with('error','Username/Password parent wajib.');
        }

        $um = new UserModel();
        $p  = $um->where('username', $parentUsername)->first();
        if (!$p) return redirect()->to('/dashboard')->with('error','Parent tidak ditemukan.');

        if (($p['role'] ?? 'user') !== 'superadmin' || (int)$p['is_active'] !== 1) {
            return redirect()->to('/dashboard')->with('error','Parent bukan superadmin / nonaktif.');
        }

        $authed = false;
        // Coba local:
        if (!empty($p['password_hash']) && password_verify($password, $p['password_hash'])) {
            $authed = true;
        } else {
            // Coba LDAP:
            try {
                $ldap = new Ldap_lib();
                $info = $ldap->login($parentUsername, $password);
                $authed = ($info !== false);
            } catch (\Throwable $e) {
                $authed = false;
            }
        }

        if (!$authed) {
            return redirect()->to('/dashboard')->with('error','Password parent salah.');
        }

        // Set session jadi parent, hapus impersonator_id
        $payload = [
            'isLoggedIn'     => true,
            'user_id'        => (int) $p['id'],
            'id'             => (int) $p['id'],
            'uid'            => $p['username'],
            'username'       => $p['username'],
            'name'           => $p['full_name'] ?? $p['username'],
            'role'           => $p['role'] ?? 'superadmin',
            'auth'           => $p['auth_source'] ?? 'local',
            'impersonator_id'=> null,
        ];
        session()->set($payload);

        // sentuh last_login_at parent (opsional)
        $um->update($p['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to('/dashboard')->with('message','Switched to parent '.$p['username']);
    }
}
