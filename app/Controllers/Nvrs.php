<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NvrModel;
use App\Libraries\Shinobi;
use CodeIgniter\Database\BaseConnection;

class Nvrs extends BaseController
{
    /** @var BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    private function guard()
    {
        if (!session('isLoggedIn')) return redirect()->to('/login');
        $role = session('role') ?? 'user';
        if (!in_array($role, ['admin','superadmin'], true)) return redirect()->to('/dashboard');
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;

        $m     = new NvrModel();
        $items = $m->orderBy('name')->findAll();

        // hitung total camera per NVR (real time dari Shinobi)
        $totals = [];
        $cli    = new Shinobi();
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
            'content' => view('nvrs/index', ['data' => $items, 'totals' => $totals]),
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

        $name     = trim((string)($this->request->getPost('name') ?? ''));
        $baseUrl  = $this->sanitizeBaseUrl((string)$this->request->getPost('base_url'));
        $apiKey   = trim((string)($this->request->getPost('api_key') ?? ''));
        $groupKey = trim((string)($this->request->getPost('group_key') ?? ''));
        $active   = (int)($this->request->getPost('is_active') ?? 1);

        if ($name === '' || $baseUrl === '' || $apiKey === '' || $groupKey === '') {
            return redirect()->back()->with('error','Nama/URL/API Key/Group Key wajib diisi.')->withInput();
        }

        // Cek duplikat sesuai unique index (name, base_url)
        $dup = $this->db->table('nvrs')
            ->where('name', $name)
            ->where('base_url', $baseUrl)
            ->get()->getFirstRow();
        if ($dup) {
            return redirect()->back()
                ->with('error', 'NVR dengan nama "'.$name.'" dan URL yang sama sudah ada.')
                ->withInput();
        }

        try {
            (new NvrModel())->insert([
                'name'      => $name,
                'base_url'  => $baseUrl,
                'api_key'   => $apiKey,
                'group_key' => $groupKey,
                'is_active' => $active,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error','Gagal menyimpan NVR: '.$e->getMessage())->withInput();
        }

        return redirect()->to('/nvrs')->with('message','NVR ditambahkan.');
    }

    public function edit($id)
    {
        if ($r = $this->guard()) return $r;

        $m    = new NvrModel();
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

        $id       = (int)$id;
        $name     = trim((string)($this->request->getPost('name') ?? ''));
        $baseUrl  = $this->sanitizeBaseUrl((string)$this->request->getPost('base_url'));
        $apiKey   = trim((string)($this->request->getPost('api_key') ?? ''));
        $groupKey = trim((string)($this->request->getPost('group_key') ?? ''));
        $active   = (int)($this->request->getPost('is_active') ?? 1);

        if ($name === '' || $baseUrl === '' || $apiKey === '' || $groupKey === '') {
            return redirect()->back()->with('error','Nama/URL/API Key/Group Key wajib diisi.')->withInput();
        }

        // Cek duplikat (name, base_url) milik record lain
        $dup = $this->db->table('nvrs')
            ->where('name', $name)
            ->where('base_url', $baseUrl)
            ->where('id !=', $id)
            ->get()->getFirstRow();
        if ($dup) {
            return redirect()->back()
                ->with('error', 'NVR dengan nama "'.$name.'" dan URL tersebut sudah ada.')
                ->withInput();
        }

        try {
            (new NvrModel())->update($id, [
                'name'      => $name,
                'base_url'  => $baseUrl,
                'api_key'   => $apiKey,
                'group_key' => $groupKey,
                'is_active' => $active,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error','Gagal update NVR: '.$e->getMessage())->withInput();
        }

        return redirect()->to('/nvrs')->with('message','NVR di-update.');
    }

    public function delete($id)
    {
        if ($r = $this->guard()) return $r;

        try {
            (new NvrModel())->delete((int)$id);
        } catch (\Throwable $e) {
            return redirect()->to('/nvrs')->with('error','Gagal delete NVR: '.$e->getMessage());
        }

        return redirect()->to('/nvrs')->with('message','NVR dihapus.');
    }
}
