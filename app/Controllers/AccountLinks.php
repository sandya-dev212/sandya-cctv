<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Libraries\Ldap_lib;
use Config\Database;

class AccountLinks extends BaseController
{
    /* ========= Helpers ========= */

    private function mustLogged()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');
        return null;
    }

    private function mustSuperadmin()
    {
        if ($r = $this->mustLogged()) return $r;
        if ((session('role') ?? '') !== 'superadmin') return redirect()->to('/dashboard');
        return null;
    }

    private function db() { return Database::connect(); }

    private function getLinkedChildIds(int $parentId): array
    {
        $rows = $this->db()->table('user_links')
            ->select('child_user_id')->where('parent_user_id', $parentId)
            ->get()->getResultArray();
        return array_map(fn($r)=> (int)$r['child_user_id'], $rows);
    }

    private function isLinked(int $parentId, int $childId): bool
    {
        $r = $this->db()->table('user_links')->where([
            'parent_user_id'=>$parentId,'child_user_id'=>$childId
        ])->get()->getFirstRow();
        return (bool)$r;
    }

    /* ========= Link UI ========= */

    // /users/link/{parentId}
    public function linkUI($parentId=null)
    {
        if ($r = $this->mustSuperadmin()) return $r;

        $parentId = (int)$parentId;
        if ($parentId<=0) return redirect()->to('/users');

        $um = new UserModel();
        $parent = $um->find($parentId);
        if (!$parent) return redirect()->to('/users');

        // Parent HARUS superadmin
        if (($parent['role'] ?? 'user') !== 'superadmin') {
            return $this->response->setStatusCode(403)->setBody('Parent harus superadmin.');
        }

        // Ambil semua user aktif kecuali parent, DAN sembunyikan superadmin (child wajib user)
        $users = $um->select('*')
            ->where('id !=', $parentId)
            ->where('is_active', 1)
            ->where('role', 'user')                // <— hanya user yang bisa jadi child
            ->orderBy('username')
            ->findAll();

        $linked = $this->getLinkedChildIds($parentId);
        $csrf   = csrf_hash();

        return $this->response->setStatusCode(200)
            ->setBody($this->renderLinkUI($parent, $users, $linked, $csrf));
    }

    private function renderLinkUI(array $parent, array $users, array $linkedIds, string $csrf): string
    {
        $parentSafe = esc($parent['username']);
        ob_start(); ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Link Accounts – <?= $parentSafe ?></title>
<style>
body{background:#0b1020;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial}
.wrap{max-width:1120px;margin:40px auto;padding:20px}
.h{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.badge{background:#1f2937;border:1px solid #374151;border-radius:999px;padding:6px 12px;font-size:12px}
.card{background:#0f172a;border:1px solid #1f2937;border-radius:16px;padding:16px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.item{display:flex;align-items:center;gap:8px;padding:10px;border-radius:10px;background:#0b1220;border:1px solid #1f2937}
.actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
.btn{padding:10px 14px;border-radius:10px;border:1px solid #374151;background:#111827;color:#e5e7eb;text-decoration:none;cursor:pointer}
.btn.primary{background:#7c3aed;border-color:#7c3aed;color:#0b1020;font-weight:700}
.search{margin-bottom:12px}
.search input{width:100%;padding:10px;border-radius:10px;border:1px solid #374151;background:#0b1220;color:#e5e7eb}
@media(max-width:1000px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.grid{grid-template-columns:1fr}}
</style></head><body>
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
        $id=(int)$u['id']; $nm=esc($u['username']); $fn=esc($u['full_name']??''); $em=esc($u['email']??'');
        $chk = in_array($id,$linkedIds,true)?'checked':'';
      ?>
      <label class="item" data-text="<?= strtolower($u['username'].' '.$u['full_name'].' '.$u['email']) ?>">
        <input type="checkbox" name="child_ids[]" value="<?= $id ?>" <?= $chk ?>>
        <div>
          <div><strong><?= $nm ?></strong> <span style="opacity:.7">(#<?= $id ?>)</span></div>
          <div style="font-size:12px;opacity:.8"><?= $fn ?> · <?= $em ?> · role: user</div>
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
const q=document.getElementById('q'), list=document.getElementById('list');
q?.addEventListener('input',()=>{const s=q.value.toLowerCase();
  list.querySelectorAll('.item').forEach(it=>{
    const t=it.getAttribute('data-text')||''; it.style.display=t.includes(s)?'':'none';
  });
});
</script>
</body></html>
<?php return ob_get_clean(); }

    // POST /users/link/save
    public function saveLinks()
    {
        if ($r = $this->mustSuperadmin()) return $r;

        $parentId = (int)$this->request->getPost('parent_id');
        $childIds = $this->request->getPost('child_ids') ?? [];
        if ($parentId<=0) return redirect()->to('/users')->with('error','Parent invalid.');
        if (!is_array($childIds)) $childIds=[];

        $um = new UserModel();
        $parent = $um->find($parentId);
        if (!$parent || ($parent['role']??'user')!=='superadmin') {
            return redirect()->to('/users')->with('error','Parent bukan superadmin.');
        }

        // hanya user (role=user) yang boleh jadi child
        $childIds = array_unique(array_filter(array_map('intval',$childIds), fn($id)=>$id>0 && $id!=$parentId));
        if ($childIds) {
            $roles = $um->select('id,role')->whereIn('id',$childIds)->findAll();
            $childIds = array_map(fn($r)=> (int)$r['id'], array_filter($roles, fn($r)=>($r['role']??'user')==='user'));
        }

        $db = $this->db();
        $db->transStart();
        $db->table('user_links')->where('parent_user_id',$parentId)->delete();
        foreach ($childIds as $cid) {
            $db->table('user_links')->insert(['parent_user_id'=>$parentId,'child_user_id'=>$cid]);
        }
        $db->transComplete();

        if (!$db->transStatus()) return redirect()->to('/users')->with('error','Gagal menyimpan link.');
        return redirect()->to('/users')->with('message','Link tersimpan.');
    }

    /* ========= Switcher Popup ========= */

    // GET /account-switcher
    public function switcherPopup()
    {
        if ($r = $this->mustLogged()) return $r;

        $userId = session('user_id');
        $role   = session('role') ?? 'user';
        $um     = new UserModel();
        $me     = $um->find($userId);
        $csrf   = csrf_hash();

        // If the switcher is superadmin, set the parentId as his id
        // and set selectedId also his id
        if ($role == 'superadmin') {
          session()->set('parentId', $userId);
          $selectedId = $userId;
        }

        // But if the switcher is from user (dashboard), set the selected id still the parent id that set before
        // to make sure even in the user dashboard, the linked user still results the childern of the parent
        $selectedId = session('parentId');

        $db = $this->db();

        $rows = $db->table('user_links ul')->select('u.*')
            ->join('users u','u.id = ul.child_user_id')
            ->where('ul.parent_user_id', $selectedId)
            ->orderBy('u.username')->get()->getResultArray();

        $data['csrf'] = $csrf;
        $data['user'] = $me;
        $data['result'] = $rows;
        return view('partials/switchAcc', $data);
    }

    /* ========= Switch Actions ========= */

    // POST /switch-as/{childId}
    public function switchAs($childId=null)
    {
        $um = new UserModel();
        $child = $um->find($childId);

        session()->set([
            'isLoggedIn'=>true,
            'user_id'=>(int)$child['id'],
            'id'=>(int)$child['id'],
            'uid'=>$child['username'],
            'username'=>$child['username'],
            'name'=>$child['full_name'] ?? $child['username'],
            'role'=>$child['role'] ?? 'user',
            'auth'=>$child['auth_source'] ?? 'local',
            'parentId'=>session('parentId'),
        ]);
        $um->update($child['id'], ['last_login_at'=>date('Y-m-d H:i:s')]);

        return redirect()->to('/dashboard/0')->with('message','Switched as '.$child['username']);
    }

    // POST /switch-to-parent
    public function switchToParent()
    {
        if ($r = $this->mustLogged()) return $r;

        $parentUsername = trim((string)$this->request->getPost('parent_username'));
        $password       = (string)$this->request->getPost('password');
        if ($parentUsername==='' || $password==='') {
            return redirect()->to('/dashboard')->with('error','Username/Password parent wajib.');
        }

        $um = new UserModel();
        $p  = $um->where('username',$parentUsername)->first();
        if (!$p || ($p['role']??'user')!=='superadmin' || (int)$p['is_active']!==1) {
            return redirect()->to('/dashboard')->with('error','Parent invalid.');
        }

        $ok = false;
        if (!empty($p['password_hash']) && password_verify($password,$p['password_hash'])) $ok=true;
        if (!$ok) {
            try { $ldap = new Ldap_lib(); $ok = ($ldap->login($parentUsername,$password)!==false); }
            catch (\Throwable $e) { $ok=false; }
        }
        if (!$ok) return redirect()->to('/dashboard')->with('error','Password parent salah.');

        session()->set([
            'isLoggedIn'=>true,
            'user_id'=>(int)$p['id'],
            'id'=>(int)$p['id'],
            'uid'=>$p['username'],
            'username'=>$p['username'],
            'name'=>$p['full_name'] ?? $p['username'],
            'role'=>$p['role'] ?? 'superadmin',
            'auth'=>$p['auth_source'] ?? 'local',
            'impersonator_id'=>null,
        ]);
        $um->update($p['id'], ['last_login_at'=>date('Y-m-d H:i:s')]);

        return redirect()->to('/dashboard')->with('message','Switched to parent '.$p['username']);
    }
}
