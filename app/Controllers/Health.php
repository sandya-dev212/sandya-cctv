<?php

namespace App\Controllers;

class Health extends BaseController
{
    public function index()
    {
        return $this->response->setJSON([
            'app' => 'Sandya NVR',
            'status' => 'ok',
            'time' => date('c'),
        ]);
    }
}
