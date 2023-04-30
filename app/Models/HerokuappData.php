<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HerokuappData extends Model
{
    use HasFactory;

    protected $table = 'herokuapp_data';

    protected $fillable = [
        'data',
        'name',
    ];
}
