<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'orgId', 'name', 'description'
    ];

    protected $primaryKey = 'orgId';

    public function users()
    {
        return $this->belongsToMany(User::class, 'organisation_user', 'organisation_id', 'userId');
    }
}
