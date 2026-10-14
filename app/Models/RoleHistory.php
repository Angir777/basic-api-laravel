<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RoleHistory extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    public $fillable = [
        'diff',
        'role_id',
        'changed_by_user_id',
        'changed_by_user_name',
        'changed_by_user_email',
        'action_type',
        'ip',
    ];
}
