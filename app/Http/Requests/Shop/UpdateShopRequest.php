<?php

namespace App\Http\Requests\Shop;

use Illuminate\Validation\Rule;

/**
 * Identical to StoreShopRequest except the unique rule must ignore the record
 * being edited, otherwise saving a shop without changing its code fails.
 */
class UpdateShopRequest extends StoreShopRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['code'] = [
            'required',
            'string',
            'max:32',
            'regex:/^[A-Za-z0-9\-_]+$/',
            Rule::unique('shops', 'code')->ignore($this->route('shop')),
        ];

        return $rules;
    }
}
