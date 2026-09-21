<?php

namespace App\Http\Requests\Lead;

class UpdateLeadRequest extends StoreLeadRequest
{
    /**
     * Authorised against the record. Any active user with Lead module access
     * may edit a lead; assignment remains a separate permission.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lead')) ?? false;
    }
}
