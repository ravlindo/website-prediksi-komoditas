<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommodityPrice extends Model
{
    use SoftDeletes;

    protected $fillable = ['commodity_id', 'market_id', 'price_date', 'price', 'source', 'deleted_by', 'deleted_ip', 'deletion_batch'];

    protected function casts(): array
    {
        return ['price_date' => 'date', 'price' => 'integer'];
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(CommodityPriceChange::class);
    }

    public function latestChange(): HasOne
    {
        return $this->hasOne(CommodityPriceChange::class)->latestOfMany();
    }
}
