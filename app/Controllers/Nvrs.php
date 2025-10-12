<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NvrModel;

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

        return view('layout/main', [
            'title'   => 'NVRs',
            'content' => view('nvrs/index', ['items' => $items]),
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
