<?php
namespace App\Models;

use CodeIgniter\Model;

class UserDashboardModel extends Model
{
    protected $table         = 'user_dashboards';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id','dashboard_id'];
    public    $timestamps    = false;
}
