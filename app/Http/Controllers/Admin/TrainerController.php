<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainerController extends Controller
{
    public function index() { return view('admin.trainers.index', ['trainers' => Trainer::query()->withCount('slots')->orderBy('sort_order')->get()]); }
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['photo_path'] = $request->file('photo')?->store('trainers', 'public');
        $data['is_active'] = $request->boolean('is_active');
        Trainer::create($data);
        return back()->with('success', 'Тренер добавлен.');
    }
    public function update(Request $request, Trainer $trainer)
    {
        $data = $this->validated($request, true);
        if ($request->hasFile('photo')) {
            if ($trainer->photo_path) Storage::disk('public')->delete($trainer->photo_path);
            $data['photo_path'] = $request->file('photo')->store('trainers', 'public');
        }
        $data['is_active'] = $request->boolean('is_active');
        $trainer->update($data);
        return back()->with('success', 'Данные тренера обновлены.');
    }
    public function destroy(Trainer $trainer)
    {
        abort_if($trainer->slots()->where('starts_at', '>=', now())->exists(), 422, 'Нельзя удалить тренера с будущими занятиями. Сначала отключите его.');
        if ($trainer->photo_path) Storage::disk('public')->delete($trainer->photo_path);
        $trainer->delete();
        return back()->with('success', 'Тренер удалён.');
    }
    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:190'], 'specialization' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'bio' => ['nullable', 'string', 'max:5000'], 'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'], 'photo' => ['nullable', 'image', 'max:5120']]);
    }
}
