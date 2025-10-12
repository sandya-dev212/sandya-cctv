<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Libraries\Ldap_lib;

class Auth extends BaseController
{
    public function login()
    {
        // Jika sudah login → langsung ke dashboard
        if (session('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        // Teruskan flash error ke view login
        $error = session('error');

        return view('layout/main', [
            'title'   => 'Login',
            'content' => view('auth/login', ['error' => $error]),
        ]);
    }

    public function doLogin()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if ($username === '' || $password === '') {
            return $this->failLogin('Username atau password wajib diisi.');
        }

        $um   = new UserModel();
        $user = $um->where('username', $username)->first();

        /**
         * 1) Local auth (kalau user ada & auth_source local/null)
         */
        if ($user && (($user['auth_source'] ?? 'local') === 'local')) {
            // user local harus punya hash
            if (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                if ((int) $user['is_active'] !== 1) {
                    return $this->failLogin('User nonaktif.');
                }
                $this->setSessionAndTouch($user);
                return redirect()->to('/dashboard');
            }
            // gagal local → lanjut ke LDAP fallback di bawah
        }

        /**
         * 2) LDAP auth (kalau user bertipe ldap atau fallback dari local)
         */
        $ldap = new Ldap_lib();
        $info = $ldap->login($username, $password); // return array info atau false

        if ($info !== false) {
            if (!$user) {
                // user belum ada → buat baru bertipe LDAP
                $user = [
                    'username'       => $info['username'] ?? $username,
                    'email'          => $info['email'] ?? null,
                    'full_name'      => $info['full_name'] ?? $username,
                    'password_hash'  => null,       // JANGAN simpan password
                    'auth_source'    => 'ldap',
                    'role'           => 'user',     // default
                    'is_active'      => 1,
                    'last_login_at'  => date('Y-m-d H:i:s'),
                ];
                $id = $um->insert($user);
                // pastikan dapat ID
                if (!$id) {
                    return $this->failLogin('Gagal membuat user LDAP.');
                }
                $user['id'] = $id;
            } else {
                // user sudah ada → pastikan ditandai ldap + update info dasar
                if (($user['auth_source'] ?? 'local') !== 'ldap') {
                    $um->update($user['id'], [
                        'auth_source'   => 'ldap',
                        'password_hash' => null, // hapus hash lokal
                    ]);
                }
                if ((int) $user['is_active'] !== 1) {
                    return $this->failLogin('User nonaktif.');
                }
                // sinkron info dari LDAP (jika ada)
                $um->update($user['id'], [
                    'email'         => $info['email'] ?? $user['email'],
                    'full_name'     => $info['full_name'] ?? $user['full_name'],
                    'last_login_at' => date('Y-m-d H:i:s'),
                ]);
                // refresh user
                $user = $um->find($user['id']);
            }

            $this->setSessionAndTouch($user);
            return redirect()->to('/dashboard');
        }

        /**
         * 3) Local gagal & LDAP gagal
         */
        return $this->failLogin('Username atau password salah.');
    }

    private function failLogin(string $msg)
    {
        return redirect()->back()->with('error', $msg)->withInput();
    }

    /**
     * Set session + update last_login_at
     * WAJIB: set 'user_id' dan 'id' untuk kompatibilitas.
     */
    private function setSessionAndTouch(array $user): void
    {
        $payload = [
            'isLoggedIn' => true,
            'user_id'    => (int) $user['id'],                 // <-- dipakai di banyak tempat
            'id'         => (int) $user['id'],                 // <-- jaga-jaga ada yang baca 'id'
            'uid'        => $user['username'],                 // identifikasi singkat
            'username'   => $user['username'],
            'name'       => $user['full_name'] ?? $user['username'],
            'role'       => $user['role'] ?? 'user',
            'auth'       => $user['auth_source'] ?? 'local',
        ];
        session()->set($payload);

        // catat last_login_at
        (new UserModel())->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
