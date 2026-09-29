<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommodityPredictionProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['data_last_date' => 'date'];
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
