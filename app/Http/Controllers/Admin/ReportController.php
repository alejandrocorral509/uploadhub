<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('estado'), [Report::STATUS_OPEN, Report::STATUS_RESOLVED, Report::STATUS_DISMISSED], true)
            ? $request->query('estado')
            : Report::STATUS_OPEN;

        $reports = Report::withReportable()
            ->where('status', $status)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports', ['reports' => $reports, 'status' => $status]);
    }

    // Resolver una denuncia: descartarla, o eliminar el contenido denunciado
    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['dismiss', 'remove'])],
        ]);

        if ($data['action'] === 'remove') {
            $report->reportable()->first()?->delete();

            // El resto de denuncias abiertas sobre el mismo contenido quedan resueltas también
            Report::where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id)
                ->where('status', Report::STATUS_OPEN)
                ->update(['status' => Report::STATUS_RESOLVED, 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        } else {
            $report->update(['status' => Report::STATUS_DISMISSED, 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        }

        return back()->with('success', $data['action'] === 'remove' ? 'Contenido eliminado.' : 'Denuncia descartada.');
    }
}
