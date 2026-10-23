<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeadsBackupExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    public function query()
    {
        return DB::table('leads')
            ->leftJoin(
                'users as assigned_user',
                'leads.assigned_to',
                '=',
                'assigned_user.id'
            )
            ->leftJoin(
                'users as creator_user',
                'leads.created_by',
                '=',
                'creator_user.id'
            )
            ->select(
                'leads.*',
                'assigned_user.name as assigned_to_name',
                'creator_user.name as created_by_name'
            )
            ->orderBy('leads.id');
    }

    public function headings(): array
    {
        return [
            'id',
            'reference',
            'name',
            'company',
            'email',
            'phone',
            'alternate_phone',
            'city',
            'source',
            'status',
            'has_quotation',
            'assigned_to_name',
            'created_by_name',
            'next_follow_up_at',
            'last_contacted_at',
            'description',
            'lost_reason',
            'closed_at',
            'created_at',
        ];
    }

    public function map($lead): array
    {
        return [
            $lead->id,
            $lead->reference,
            $lead->name,
            $lead->company,
            $lead->email,
            $lead->phone,
            $lead->alternate_phone,
            $lead->city,
            $lead->source,
            $lead->status,
            $lead->has_quotation,
            $lead->assigned_to_name,
            $lead->created_by_name,
            $lead->next_follow_up_at,
            $lead->last_contacted_at,
            html_entity_decode(
                strip_tags($lead->description ?? ''),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ),
            $lead->lost_reason,
            $lead->closed_at,
            $lead->created_at,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);

        $sheet->getStyle('A1:S1')
            ->getAlignment()
            ->setVertical('center');

        $sheet->getStyle('A:S')
            ->getAlignment()
            ->setVertical('top');

        // Description column: wrap text
        $sheet->getStyle('P:P')
            ->getAlignment()
            ->setWrapText(true);

        // Keep long descriptions from making the column too wide
        $sheet->getColumnDimension('P')->setWidth(45);

        // Header row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }
}