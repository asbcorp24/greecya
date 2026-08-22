@foreach($groupSettings as $setting)
<div class="col-12 {{ in_array($setting->type, ['textarea', 'image'], true) ? '' : 'col-lg-6' }}">
    <label class="form-label fw-bold" for="setting_{{ $setting->key }}">{{ $setting->label }}</label>
    @if($setting->type === 'textarea')
        <textarea class="form-control" id="setting_{{ $setting->key }}" name="settings[{{ $setting->key }}]" rows="4">{{ old('settings.'.$setting->key, $setting->value) }}</textarea>
    @elseif($setting->type === 'boolean')
        <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
        <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" id="setting_{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="1" @checked(old('settings.'.$setting->key, $setting->value))><label class="form-check-label" for="setting_{{ $setting->key }}">Включено</label></div>
    @elseif($setting->type === 'image')
        @if($setting->value)<div class="mb-2"><img src="{{ Storage::url($setting->value) }}" alt="" style="max-height:100px;max-width:260px" class="rounded border"></div>@endif
        <input class="form-control" type="file" id="setting_{{ $setting->key }}" name="files[{{ $setting->key }}]" accept="image/*">
    @else
        <input class="form-control" type="{{ in_array($setting->type, ['email','url','number'], true) ? $setting->type : 'text' }}" id="setting_{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="{{ old('settings.'.$setting->key, $setting->value) }}">
    @endif
    <div class="form-text"><code>{{ $setting->key }}</code></div>
</div>
@endforeach
