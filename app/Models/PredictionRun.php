<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data_last_date' => 'date',
            'generated_at' => 'datetime',
            'activated_at' => 'datetime',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'status_summary' => 'array',
        ];
    }

    public function predictions()
    {
        return $this->hasMany(CommodityPrediction::class);
    }

    public function profiles()
    {
        return $this->hasMany(CommodityPredictionProfile::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
