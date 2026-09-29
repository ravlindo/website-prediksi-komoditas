<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InfographicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $imageRule = $this->route('infographic') ? ['nullable'] : ['required'];

        return [
            'title' => ['required', 'string', 'max:180'],
            'agency' => ['required', 'string', Rule::in(config('infographics.data_sources'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => [...$imageRule, 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'publication_year' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Gambar infografis wajib dipilih.',
            'image.image' => 'File harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 8 MB.',
            'agency.required' => 'Sumber data wajib dipilih.',
            'agency.in' => 'Sumber data yang dipilih tidak tersedia.',
        ];
    }
}
