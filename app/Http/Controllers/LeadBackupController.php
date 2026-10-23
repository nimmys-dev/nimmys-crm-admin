<?php

namespace App\Http\Controllers;

use App\Exports\BackupWorkbookExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LeadBackupController extends Controller
{
    public function export()
    {
        abort_unless(
            Auth::check()
            && Auth::user()->role->value === 'admin',
            403
        );

        return Excel::download(
            new BackupWorkbookExport(),
            'leads_backup_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }
}