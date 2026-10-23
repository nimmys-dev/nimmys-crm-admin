<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadCallDetailsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return DB::table('lead_call_details')->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'id', 'lead_id', 'call_status', 'remarks', 'called_by',
            'called_date', 'called_time', 'next_followup_date',
            'duration', 'interest', 'reason', 'is_item_sold',
            'invoice_number', 'invoice_file_path',
            'created_at', 'updated_at',
        ];
    }

    public function map($call): array
    {
        return [
            $call->id,
            $call->lead_id,
            $call->call_status,
            $call->remarks,
            $call->called_by,
            $call->called_date,
            $call->called_time,
            $call->next_followup_date,
            $call->duration,
            $call->interest,
            $call->reason,
            $call->is_item_sold,
            $call->invoice_number,
            $call->invoice_file_path,
            $call->created_at,
            $call->updated_at,
        ];
    }
}
