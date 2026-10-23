<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BackupWorkbookExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new LeadsBackupExport(),
            new LeadCallDetailsExport(),
        ];
    }
}