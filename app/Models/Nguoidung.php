<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nguoidung extends Model
{
    protected $table = 'nguoidung';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'password',
        'ten_nguoi_dung',
        'role',
    ];
}