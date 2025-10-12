<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'username'      => 'administrator',
            'email'         => 'administrator@sandya.net.id',
            'full_name'     => 'Super Admin',
            'password_hash' => password_hash('asamsulfat-h2so4', PASSWORD_BCRYPT),
            'auth_source'   => 'local',
            'role'          => 'superadmin',
            'is_active'     => 1,
            'last_login_at' => null,
        ];

        // upsert by username
        $builder = $this->db->table('users');
        $exists  = $builder->where('username', $data['username'])->get()->getRowArray();

        if ($exists) {
            $builder->where('id', $exists['id'])->update($data);
        } else {
            $builder->insert($data);
        }
    }
}
