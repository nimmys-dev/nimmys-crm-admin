<?php

namespace App\Http\Requests\CallDetail;

class UpdateCallDetailRequest extends StoreCallDetailRequest
{
    /**
     * Authorised against the record, so an Employee may correct only the
     * entries they logged themselves.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('call')) ?? false;
    }

    protected function isInvoiceFileRequired(): bool
    {
        $call = $this->route('call');
        if ($call && $call->invoice_file_path) {
            return false;
        }

        return parent::isInvoiceFileRequired();
    }
}
