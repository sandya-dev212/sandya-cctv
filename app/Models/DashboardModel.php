<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $table         = 'dashboards';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name','description','created_by'];
    protected $returnType    = 'array';
}
