<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardMonitorModel extends Model
{
    protected $table         = 'dashboard_monitors';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dashboard_id','nvr_id','monitor_id','alias','sort_order'];
    protected $returnType    = 'array';
}
