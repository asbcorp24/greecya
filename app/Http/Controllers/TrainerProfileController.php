<?php

namespace App\Http\Controllers;

use App\Models\Trainer;

class TrainerProfileController extends Controller
{
    public function show(Trainer $trainer)
    {
        abort_unless($trainer->is_active, 404);

        $trainer->load([
            'photos' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return view('trainers.show', compact('trainer'));
    }
}
