<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commodity extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'name', 'slug', 'unit', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CommodityPrice::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(CommodityPrediction::class);
    }

    public function predictionProfile(): HasOne
    {
        return $this->hasOne(CommodityPredictionProfile::class);
    }
}
