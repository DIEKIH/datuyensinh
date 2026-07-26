<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Baiviet extends Model
{
    protected $table = 'baiviet';

    // We don't strictly define all fillables here unless we know them, 
    // but we can use guarded = [] to allow all for now since it's a legacy table
    protected $guarded = [];
}
