<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Show;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['audio', 'show', 'comment'])],
            'id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $class = Relation::getMorphedModel($data['type']);
        $reportable = $class::findOrFail($data['id']);

        // No se puede denunciar lo que no se puede ver
        $visible = match (true) {
            $reportable instanceof Audio => Gate::allows('view', $reportable),
            $reportable instanceof Comment => Gate::allows('view', $reportable->audio()->first()),
            $reportable instanceof Show => true,
        };
        abort_unless($visible, 404);

        Report::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'reportable_type' => $data['type'],
                'reportable_id' => $reportable->getKey(),
                'status' => Report::STATUS_OPEN,
            ],
            ['reason' => $data['reason'], 'details' => $data['details'] ?? null],
        );

        return back()->with('success', 'Gracias. El equipo de moderación revisará la denuncia.');
    }
}
