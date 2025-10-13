<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Users extends BaseController
{
    private function guard() {
        if (!session('isLoggedIn')) return redirect()->to('/login');
        if ((session('role') ?? '') !== 'superadmin') return redirect()->to('/dashboard');
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;

        // Penting: paksa ambil SEMUA kolom, menghindari Model yg ngebatesin SELECT
        $um    = new UserModel();
        $items = $um->select('*')->orderBy('username')->findAll();

        return view('layout/main', [
            'title'   => 'Users',
            'content' => view('users/index', ['items' => $items]),
        ]);
    }

    public function create()
    {
        if ($r = $this->guard()) return $r;

        return view('layout/main', [
            'title'   => 'Add Local User',
            'content' => view('users/form', ['mode'=>'create','item'=>null]),
        ]);
    }

    public function store()
    {
        if ($r = $this->guard()) return $r;

        $username = trim((string)$this->request->getPost('username'));
        $email    = trim((string)$this->request->getPost('email'));
        $full     = trim((string)$this->request->getPost('full_name'));
        $role     = trim((string)$this->request->getPost('role')) ?: 'user';
        $pass     = (string)$this->request->getPost('password');

        if ($username === '' || $pass === '') {
            return redirect()->back()->with('error','Username & Password wajib.')->withInput();
        }

        $um = new UserModel();
        $um->insert([
            'username'      => $username,
            'email'         => $email,
            'full_name'     => $full ?: $username,
            'password_hash' => password_hash($pass, PASSWORD_BCRYPT),
            'auth_source'   => 'local',
            'role'          => in_array($role, ['user','admin','superadmin']) ? $role : 'user',
            'is_active'     => 1,
        ]);

        return redirect()->to('/users');
    }

    public function edit($id)
    {
        if ($r = $this->guard()) return $r;

        $item = (new UserModel())->find((int)$id);
        if (!$item) return redirect()->to('/users');

        return view('layout/main', [
            'title'   => 'Edit User',
            'content' => view('users/form', ['mode'=>'edit','item'=>$item]),
        ]);
    }

    public function update($id)
    {
        if ($r = $this->guard()) return $r;

        $um   = new UserModel();
        $item = $um->find((int)$id);
        if (!$item) return redirect()->to('/users');

        $auth   = ($item['auth_source'] ?? $item['auth'] ?? $item['auth_type'] ?? $item['provider'] ?? $item['auth_provider'] ?? '') ?: 'local';
        $auth   = strtolower($auth);
        $role   = trim((string)$this->request->getPost('role')) ?: $item['role'];
        $active = (int)$this->request->getPost('is_active') ? 1 : 0;

        $data = [
            'role'      => in_array($role, ['user','admin','superadmin']) ? $role : $item['role'],
            'is_active' => $active,
        ];

        if ($auth === 'local') {
            $email  = trim((string)$this->request->getPost('email'));
            $full   = trim((string)$this->request->getPost('full_name'));
            $pass   = (string)$this->request->getPost('password');

            $data['email']     = $email;
            $data['full_name'] = $full ?: $item['username'];
            if ($pass !== '') $data['password_hash'] = password_hash($pass, PASSWORD_BCRYPT);
        }

        $um->update($item['id'], $data);
        return redirect()->to('/users');
    }

    public function delete($id)
    {
        if ($r = $this->guard()) return $r;

        $um   = new UserModel();
        $item = $um->find((int)$id);
        if (!$item) return redirect()->to('/users');

        $um->delete($item['id']);
        return redirect()->to('/users');
    }
}
