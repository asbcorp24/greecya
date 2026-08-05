<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function general()
    {
        return view('admin.settings.general', [
            'settings' => SiteSetting::query()->whereIn('group', ['site', 'promo'])->orderBy('group')->orderBy('sort_order')->get()->groupBy('group'),
        ]);
    }

    public function contacts()
    {
        return view('admin.settings.contacts', [
            'settings' => SiteSetting::query()->whereIn('group', ['contacts', 'schedule', 'social', 'company'])->orderBy('group')->orderBy('sort_order')->get()->groupBy('group'),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $this->save($request, ['site', 'promo']);
        return back()->with('success', 'Настройки сайта сохранены.');
    }

    public function updateContacts(Request $request)
    {
        $this->save($request, ['contacts', 'schedule', 'social', 'company']);
        return back()->with('success', 'Контакты и реквизиты сохранены.');
    }

    private function save(Request $request, array $groups): void
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:20000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $settings = SiteSetting::query()->whereIn('group', $groups)->get();

        foreach ($settings as $setting) {
            if ($setting->type === 'image' && $request->hasFile('files.'.$setting->key)) {
                if ($setting->value) {
                    Storage::disk('public')->delete($setting->value);
                }
                $setting->value = $request->file('files.'.$setting->key)->store('settings', 'public');
            } elseif ($setting->type === 'boolean') {
                $setting->value = $request->boolean('settings.'.$setting->key) ? '1' : '0';
            } elseif ($request->has('settings.'.$setting->key)) {
                $setting->value = $request->input('settings.'.$setting->key);
            }

            $setting->save();
        }

        SiteSettings::flush();
    }
}
