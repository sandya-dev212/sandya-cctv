<?php

namespace App\Models;

use CodeIgniter\Model;

class NvrModel extends Model
{
    protected $table         = 'nvrs';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name','base_url','api_key','group_key','is_active'];
    protected $returnType    = 'array';
}
