<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommodityPrediction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'data_last_date' => 'date',
            'model_generated_at' => 'datetime',
            'interval_adjusted' => 'boolean',
        ];
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PredictionRun::class, 'prediction_run_id');
    }
}
