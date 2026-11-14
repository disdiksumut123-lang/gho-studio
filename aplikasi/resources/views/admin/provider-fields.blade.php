<div class="field"><label>{{ __('Nama') }}</label><input type="text" name="label" value="{{ $p->label }}" required></div>
<div class="field"><label>{{ __('Jenis sambungan') }}</label><select name="kind">
  <option value="openai_compatible" @selected($p->kind === 'openai_compatible')>{{ __('Kompatibel OpenAI (DeepSeek, OpenAI, OpenRouter, Groq)') }}</option>
  <option value="gemini" @selected($p->kind === 'gemini')>Google Gemini</option>
  <option value="anthropic" @selected($p->kind === 'anthropic')>Anthropic Claude</option></select></div>
<div class="field"><label>{{ __('Alamat API') }}</label><input type="url" name="base_url" value="{{ $p->base_url }}"></div>
<div class="field"><label>{{ __('Model') }}</label><input type="text" name="model" value="{{ $p->model }}" required></div>
<div class="field"><label>{{ __('Kunci API') }}</label><input type="password" name="api_key" autocomplete="off" placeholder="{{ $p->exists ? $p->keyHint() : '' }}" {{ $p->exists ? '' : 'required' }}>@if ($p->exists)<span class="hint">{{ __('Kosongkan bila tidak diganti.') }}</span>@endif</div>
<div class="field"><label>{{ __('Batas token jawaban') }}</label><input type="number" name="max_tokens" min="256" max="64000" value="{{ $p->max_tokens ?? 4000 }}"></div>
<label class="sw"><input type="checkbox" name="enabled" value="1" @checked($p->enabled ?? true)> {{ __('Aktif') }}</label>
