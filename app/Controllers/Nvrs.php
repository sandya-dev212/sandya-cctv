<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NvrModel;
use App\Libraries\Shinobi;

class Nvrs extends BaseController
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

        $m = new NvrModel();
        $items = $m->orderBy('name')->findAll();

        // hitung total camera per NVR (real time dari Shinobi)
        $totals = [];
        $cli = new Shinobi();
        foreach ($items as $n) {
            $cnt = 0;
            if ((int)$n['is_active'] === 1) {
                $resp = $cli->getMonitors($n['base_url'], $n['api_key'], $n['group_key'], null);
                if ($resp['ok'] && is_array($resp['data'])) {
                    $cnt = count($cli->normalizeMonitors($resp['data']));
                }
            }
            $totals[$n['id']] = $cnt;
        }

        return view('layout/main', [
            'title'   => 'NVRs',
            'content' => view('nvrs/index', ['items' => $items, 'totals' => $totals]),
        ]);
    }

    public function create()
    {
        if ($r = $this->guard()) return $r;

        return view('layout/main', [
            'title'   => 'Add NVR',
            'content' => view('nvrs/form', ['action'=>'create','item'=>null]),
        ]);
    }

    private function sanitizeBaseUrl(string $u): string
    {
        $u = trim($u);
        if ($u === '') return $u;
        if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
        return rtrim($u, '/');
    }

    public function store()
    {
        if ($r = $this->guard()) return $r;

        $data = [
            'name'      => trim($this->request->getPost('name') ?? ''),
            'base_url'  => $this->sanitizeBaseUrl((string)$this->request->getPost('base_url')),
            'api_key'   => trim($this->request->getPost('api_key') ?? ''),
            'group_key' => trim($this->request->getPost('group_key') ?? ''),
            'is_active' => (int)($this->request->getPost('is_active') ?? 1),
        ];
        (new NvrModel())->insert($data);
        return redirect()->to('/nvrs');
    }

    public function edit($id)
    {
        if ($r = $this->guard()) return $r;

        $m = new NvrModel();
        $item = $m->find((int)$id);
        if (!$item) return redirect()->to('/nvrs');

        return view('layout/main', [
            'title'=>'Edit NVR',
            'content'=>view('nvrs/form', ['action'=>'edit','item'=>$item]),
        ]);
    }

    public function update($id)
    {
        if ($r = $this->guard()) return $r;

        $data = [
            'name'      => trim($this->request->getPost('name') ?? ''),
            'base_url'  => $this->sanitizeBaseUrl((string)$this->request->getPost('base_url')),
            'api_key'   => trim($this->request->getPost('api_key') ?? ''),
            'group_key' => trim($this->request->getPost('group_key') ?? ''),
            'is_active' => (int)($this->request->getPost('is_active') ?? 1),
        ];
        (new NvrModel())->update((int)$id, $data);
        return redirect()->to('/nvrs');
    }

    public function delete($id)
    {
        if ($r = $this->guard()) return $r;
        (new NvrModel())->delete((int)$id);
        return redirect()->to('/nvrs');
    }
}
