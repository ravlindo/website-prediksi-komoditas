<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommodityPriceChange extends Model
{
    protected $fillable = [
        'commodity_price_id', 'user_id', 'user_name', 'user_email',
        'old_price', 'new_price', 'changes', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array', 'old_price' => 'integer', 'new_price' => 'integer'];
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(CommodityPrice::class, 'commodity_price_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
