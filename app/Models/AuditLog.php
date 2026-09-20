<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
