<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/VisitorLog.php
class VisitorLog extends Model {
    protected $fillable = ['ip', 'visited_date'];
}
