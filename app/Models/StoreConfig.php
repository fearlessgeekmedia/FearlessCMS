<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreConfig extends Model
{
    protected $table = 'store_config';
    
    protected $fillable = [
        'store_url',
        'last_sync',
    ];

    protected $casts = [
        'last_sync' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
} 