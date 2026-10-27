<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->latest('id')
            ->paginate(25);

        return view('admin.audit-logs.index', ['logs' => $logs]);
    }
}
