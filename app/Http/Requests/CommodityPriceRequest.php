<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommodityPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commodity_id' => ['required', 'integer', 'exists:commodities,id'],
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'price_date' => [
                'required', 'date',
                Rule::unique('commodity_prices')->where(fn ($query) => $query
                    ->where('commodity_id', $this->integer('commodity_id'))
                    ->where('market_id', $this->integer('market_id'))
                    ->where('price_date', $this->input('price_date'))
                    ->whereNull('deleted_at'))
                    ->ignore($this->route('commodityPrice')),
            ],
            'price' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'source' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['price_date.unique' => 'Data untuk komoditas, pasar, dan tanggal tersebut sudah tersedia. Silakan ubah data yang lama.'];
    }
}
