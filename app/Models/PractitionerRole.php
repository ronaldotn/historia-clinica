<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PractitionerRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'practitioner_id',
        'organization_id',
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function practitioner()
    {
        return $this->belongsTo(Practitioner::class);
    }
}
