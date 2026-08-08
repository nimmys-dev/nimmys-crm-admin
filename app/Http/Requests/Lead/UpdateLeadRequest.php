<?php

namespace App\Http\Requests\Lead;

class UpdateLeadRequest extends StoreLeadRequest
{
    /**
     * Authorised against the record, so an Employee may only edit a lead
     * assigned to them.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lead')) ?? false;
    }
}
