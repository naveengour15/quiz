<!-- Profile Edit Modal -->
<div id="mode-edit" class="fsr-modal-overlay" onclick="if(event.target === this) closeProfileEditModal()">
    <div class="fsr-modal-panel">
        <div class="fsr-modal-handle"></div>
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-display text-lg font-bold text-[#e8ddff]">{{ __('app.update_profile') }}</h3>
            <button type="button" onclick="closeProfileEditModal()" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form id="profile-edit-form" action="{{ route('fsrapp.profile.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4 mb-1">
            @csrf
            <div class="glass-card rounded-2xl p-5 flex flex-col gap-4 border border-white/10 bg-white/[0.02]">
                <div class="flex flex-col items-center mb-1">
                    <div class="relative">
                        <div class="pf-neon-ring w-24 h-24">
                            <div id="pf-photo-preview" class="w-full h-full rounded-full overflow-hidden">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full rounded-full bg-gradient-to-tr from-[#7c3aed] to-[#d946ef] flex items-center justify-center">
                                        <span class="material-symbols-outlined text-white text-4xl">person</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <button type="button" id="pf-photo-btn" class="pf-photo-btn absolute bottom-0 right-0 w-8 h-8 rounded-full bg-[#ffd76a] text-[#160d2f] flex items-center justify-center border-2 border-[#160d2f]">
                            <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                        </button>
                        <input type="file" id="pf-photo-input" name="profile_photo" accept="image/*" class="hidden">
                    </div>
                </div>
                <div>
                    <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ __('app.full_name') }}</label>
                    <input id="f-name" name="name" type="text" value="{{ $user->name }}" placeholder="Your name" class="mt-1 w-full bg-white/[0.05] border border-white/15 focus:border-[#d2bbff] rounded-xl px-4 py-3 text-[#e8ddff] placeholder:text-white/40 text-xs font-semibold focus:outline-none" readonly>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <p class="pf-section-label">{{ __('app.contact_verification') }}</p>
                <div class="pf-contact-row opacity-70">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <span class="material-symbols-outlined text-[#d2bbff] text-[20px] shrink-0">call</span>
                        <input id="f-mobile" type="tel" value="{{ $user->mobile }}" class="bg-transparent flex-1 min-w-0 text-[#e8ddff] text-xs font-semibold placeholder:text-white/40 focus:outline-none" readonly>
                    </div>
                    <div id="edit-verify-mobile">
                        @if ($user->verify_mobile)
                            <span class="pf-verified-pill"><span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1;">verified</span>{{ __('app.verified') }}</span>
                        @else
                            <button type="button" onclick="verifyMobile()" class="pf-unverified-pill"><span class="material-symbols-outlined text-[13px]">error</span>{{ __('app.verify_now') }}</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5 flex flex-col gap-4 border border-white/10 bg-white/[0.02]">
                <p class="pf-section-label">{{ __('app.personal_details') }}</p>
                <div>
                    <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">ALTERNATIVE MOBILE</label>
                    <input id="f-alt_mobile" name="alternative_mobile" type="tel" value="{{ $user->alternative_mobile }}" placeholder="98765 43210" class="mt-1 w-full bg-white/[0.05] border border-white/15 focus:border-[#d2bbff] rounded-xl px-4 py-3 text-[#e8ddff] placeholder:text-white/40 text-xs font-semibold focus:outline-none" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ __('app.state') }}</label>
                        <select id="f-state" name="state" class="mt-1 w-full bg-[#1a0f37] border border-white/15 focus:border-[#d2bbff] rounded-xl px-4 py-3 text-[#e8ddff] text-xs font-semibold focus:outline-none">
                            <option value="">{{ __('app.select_state') }}</option>
                            @foreach ($states as $state)
                                @php
                                    $sName = is_object($state) ? $state->name : (is_array($state) ? ($state['name'] ?? $state) : $state);
                                    $sId = is_object($state) ? $state->id : (is_array($state) ? ($state['id'] ?? '') : '');
                                @endphp
                                <option value="{{ $sName }}" data-state-id="{{ $sId }}" {{ $user->state == $sName ? 'selected' : '' }}>{{ $sName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ __('app.city') }}</label>
                        <select id="f-city" name="city" class="mt-1 w-full bg-[#1a0f37] border border-white/15 focus:border-[#d2bbff] rounded-xl px-4 py-3 text-[#e8ddff] text-xs font-semibold focus:outline-none">
                            <option value="">{{ __('app.select_city') }}</option>
                            @if ($user->city)
                                <option value="{{ $user->city }}" selected>{{ $user->city }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div>
                    <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ __('app.address') }}</label>
                    <textarea id="f-address" name="address" rows="2" placeholder="House no, street, area" class="mt-1 w-full bg-white/[0.05] border border-white/15 focus:border-[#d2bbff] rounded-xl px-4 py-3 text-[#e8ddff] placeholder:text-white/40 text-xs font-semibold focus:outline-none resize-none">{{ $user->address }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ strtoupper(__('app.date_of_birth')) }}</label>
                        <input id="f-dob" name="date_of_birth" type="date" value="{{ $user->date_of_birth }}" class="mt-1 w-full bg-white/[0.05] border border-white/15 focus:border-[#d2bbff] rounded-xl px-4 py-3 text-[#e8ddff] text-xs font-semibold focus:outline-none">
                    </div>
                    <div>
                        <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ strtoupper(__('app.gender')) }}</label>
                        <select id="f-gender" name="gender" class="mt-1 w-full bg-[#1a0f37] border border-white/15 focus:border-[#d2bbff] rounded-xl px-4 py-3 text-[#e8ddff] text-xs font-semibold focus:outline-none">
                            <option value="">{{ __('app.select') }}</option>
                            <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>{{ __('app.male') }}</option>
                            <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>{{ __('app.female') }}</option>
                            <option value="other" {{ $user->gender == 'other' ? 'selected' : '' }}>{{ __('app.other') }}</option>
                        </select>
                    </div>
                </div>
                @php
                    $favoriteColorSelected = is_array($user->favorite_color)
                        ? $user->favorite_color
                        : (json_decode((string) $user->favorite_color, true) ?: []);
                @endphp
                <div>
                    <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ __('app.favorite_color') }}</label>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <button type="button" onclick="openFavoriteColorModal()" id="favorite_color-selected-label" class="">
                            <span id="favorite_color-button-text" class="pf-chip bg-[#7c3aed]/20 text-[#d2bbff] border-[#7c3aed]/40">Add +</span>
                        </button>
                        <div id="favorite_color-selected-chips" class="flex flex-wrap items-center gap-1.5"></div>
                    </div>
                    <input type="hidden" id="f-color" name="favorite_color" value="{{ json_encode($favoriteColorSelected) }}">
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5 flex flex-col gap-4 border border-white/10 bg-white/[0.02]">
                <p class="pf-section-label">{{ __('app.preferences') }}</p>
                @foreach ([
                    'hobbies' => ['label' => __('app.hobbies'), 'title' => __('app.what_are_your_hobbies'), 'options' => $quizHobbies],
                    'games' => ['label' => __('app.games'), 'title' => __('app.which_games_do_you_play'), 'options' => $quizGames],
                    'interest' => ['label' => __('app.interests'), 'title' => __('app.what_are_you_interested_in'), 'options' => $quizInterests],
                ] as $field => $preference)
                    @php
                        $rawPreferences = $user->{$field};
                        $selectedPreferences = is_array($rawPreferences) ? $rawPreferences : (json_decode((string) $rawPreferences, true) ?: []);
                        if (! is_array($selectedPreferences)) {
                            $selectedPreferences = [];
                        }
                        if (! is_array($rawPreferences) && $selectedPreferences === [] && $rawPreferences) {
                            $selectedNames = array_map('trim', explode(',', (string) $rawPreferences));
                            $selectedPreferences = collect($preference['options'])
                                ->filter(fn ($option) => in_array($option->name, $selectedNames, true))
                                ->map(fn ($option) => ['id' => $option->id, 'name' => $option->name])
                                ->values()
                                ->all();
                        }
                    @endphp
                    <div>
                        <label class="font-mono text-[10px] text-white/50 tracking-wider uppercase">{{ $preference['label'] }}</label>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <button type="button" onclick="openPreferenceModal('{{ $field }}')" id="{{ $field }}-selected-label" class="">
                                <span class="pf-chip bg-[#7c3aed]/20 text-[#d2bbff] border-[#7c3aed]/40">Add +</span>
                            </button>
                            <div id="{{ $field }}-selected-chips" class="flex flex-wrap items-center gap-1.5"></div>
                        </div>
                        <input type="hidden" id="f-{{ $field }}" name="{{ $field }}" value="{{ json_encode($selectedPreferences) }}">
                    </div>
                @endforeach
            </div>

            <button type="submit" id="save-profile-btn" class="w-full mt-1 bg-gradient-to-r from-[#7c3aed] to-[#d946ef] text-white py-3.5 rounded-full font-bold text-sm active:scale-95 transition-transform shadow-lg shadow-[#7c3aed]/25">{{ __('app.save_changes') }}</button>
            <p id="profile-message" class="text-center text-xs hidden"></p>
        </form>
    </div>
</div>

<div id="favorite_color-preference-modal" class="fsr-modal-overlay" onclick="if(event.target === this) closeFavoriteColorModal()">
    <div class="fsr-modal-panel">
        <div class="fsr-modal-handle"></div>
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-display text-lg font-bold text-[#e8ddff]">{{ __('app.choose_favorite_color') }}</h3>
            <button type="button" onclick="closeFavoriteColorModal()" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        <div class="flex flex-wrap gap-2 max-h-[55vh] overflow-y-auto">
            @foreach ($quizColors as $color)
                <button type="button" data-preference-option="favorite_color" data-id="{{ $color->id }}" data-name="{{ $color->name }}" onclick="toggleFavoriteColor(this)" class="preference-option px-3 py-2 rounded-full bg-white/10 border border-white/15 text-white/75 text-xs font-semibold">
                    {{ $color->name }}
                </button>
            @endforeach
        </div>
        <button type="button" onclick="saveFavoriteColorSelection()" class="w-full mt-5 py-3 rounded-full bg-gradient-to-r from-[#7c3aed] to-[#d946ef] text-white font-bold text-sm">{{ __('app.save_color') }}</button>
    </div>
</div>

@foreach ([
    'hobbies' => ['title' => __('app.what_are_your_hobbies'), 'options' => $quizHobbies],
    'games' => ['title' => __('app.which_games_do_you_play'), 'options' => $quizGames],
    'interest' => ['title' => __('app.what_are_you_interested_in'), 'options' => $quizInterests],
] as $field => $preference)
    <div id="{{ $field }}-preference-modal" class="fsr-modal-overlay" onclick="if(event.target === this) closePreferenceModal('{{ $field }}')">
        <div class="fsr-modal-panel">
            <div class="fsr-modal-handle"></div>
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display text-lg font-bold text-[#e8ddff]">{{ $preference['title'] }}</h3>
                <button type="button" onclick="closePreferenceModal('{{ $field }}')" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
            <div class="flex flex-wrap gap-2 max-h-[55vh] overflow-y-auto">
                @foreach ($preference['options'] as $option)
                    <button type="button" data-preference-option="{{ $field }}" data-id="{{ $option->id }}" data-name="{{ $option->name }}" onclick="togglePreference('{{ $field }}', this)" class="preference-option px-3 py-2 rounded-full bg-white/10 border border-white/15 text-white/75 text-xs font-semibold">
                        {{ $option->name }}
                    </button>
                @endforeach
            </div>
            <button type="button" onclick="closePreferenceModal('{{ $field }}')" class="w-full mt-5 py-3 rounded-full bg-gradient-to-r from-[#7c3aed] to-[#d946ef] text-white font-bold text-sm">{{ __('app.add_selected') }}</button>
        </div>
    </div>
@endforeach

@push('scripts')
<script>
    const profilePreferences = {};

    function parseFavoriteColors() {
        try {
            const parsed = JSON.parse(document.getElementById('f-color')?.value || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function openFavoriteColorModal() {
        const modal = document.getElementById('favorite_color-preference-modal');
        if (!modal) return;
        const selected = parseFavoriteColors();
        document.querySelectorAll('[data-preference-option="favorite_color"]').forEach(button => {
            const active = selected.some(option => option.name === button.dataset.name);
            button.classList.toggle('bg-[#d946ef]', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-white/10', !active);
        });
        modal.classList.add('open');
        document.body.classList.add('overflow-hidden');
    }

    function closeFavoriteColorModal() {
        const modal = document.getElementById('favorite_color-preference-modal');
        if (!modal) return;
        modal.classList.remove('open');
        document.body.classList.remove('overflow-hidden');
    }

    function renderFavoriteColorSelection() {
        const selected = parseFavoriteColors();
        const colorText = document.getElementById('favorite_color-button-text');
        const chips = document.getElementById('favorite_color-selected-chips');

        if (colorText) {
            colorText.textContent = selected.length ? 'Add More +' : 'Add +';
        }

        if (chips) {
            chips.innerHTML = selected.length
                ? selected.map(option => `
                    <span class="pf-chip bg-[#7c3aed]/20 text-[#d2bbff] border-[#7c3aed]/40">${option.name}</span>
                `).join('')
                : '';
        }
    }

    function toggleFavoriteColor(button) {
        const item = { id: Number(button.dataset.id), name: button.dataset.name };
        const selected = parseFavoriteColors();
        const index = selected.findIndex(option => Number(option.id || 0) === Number(item.id || 0) || option.name === item.name);

        if (index >= 0) {
            selected.splice(index, 1);
        } else {
            selected.push(item);
        }

        document.getElementById('f-color').value = JSON.stringify(selected);
        document.querySelectorAll('[data-preference-option="favorite_color"]').forEach(itemButton => {
            const active = selected.some(option => option.name === itemButton.dataset.name);
            itemButton.classList.toggle('bg-[#d946ef]', active);
            itemButton.classList.toggle('text-white', active);
            itemButton.classList.toggle('bg-white/10', !active);
        });
        renderFavoriteColorSelection();
    }

    function saveFavoriteColorSelection() {
        closeFavoriteColorModal();
    }

    function openPreferenceModal(field) {
        const modal = document.getElementById(`${field}-preference-modal`);
        if (!modal) return;
        profilePreferences[field] = JSON.parse(document.getElementById(`f-${field}`).value || '[]');
        syncPreferenceOptions(field);
        modal.classList.add('open');
        document.body.classList.add('overflow-hidden');
    }

    function closePreferenceModal(field) {
        const modal = document.getElementById(`${field}-preference-modal`);
        if (!modal) return;
        modal.classList.remove('open');
        document.body.classList.remove('overflow-hidden');
    }

    function togglePreference(field, button) {
        const item = { id: Number(button.dataset.id), name: button.dataset.name };
        const selected = profilePreferences[field] || [];
        const index = selected.findIndex(option => Number(option.id) === item.id);
        if (index >= 0) selected.splice(index, 1);
        else selected.push(item);
        profilePreferences[field] = selected;
        syncPreferenceOptions(field);
        renderPreferenceChips(field);
    }

    function syncPreferenceOptions(field) {
        const selected = profilePreferences[field] || [];
        document.querySelectorAll(`[data-preference-option="${field}"]`).forEach(button => {
            const active = selected.some(option => Number(option.id) === Number(button.dataset.id));
            button.classList.toggle('bg-[#d946ef]', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-white/10', !active);
        });
    }

    function renderPreferenceChips(field) {
        const selected = Array.isArray(profilePreferences[field]) ? profilePreferences[field] : [];
        const hiddenInput = document.getElementById(`f-${field}`);
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(selected);
        }

        const chips = document.getElementById(`${field}-selected-chips`);
        const label = document.getElementById(`${field}-selected-label`);
        if (!label) return;

        const labelText = selected.length ? 'Add More +' : 'Add +';
        label.innerHTML = `<span class="pf-chip bg-[#7c3aed]/20 text-[#d2bbff] border-[#7c3aed]/40">${labelText}</span>`;

        if (!chips) return;
        chips.innerHTML = selected.length
            ? selected.map(option => `
                <span class="pf-chip bg-[#7c3aed]/20 text-[#d2bbff] border-[#7c3aed]/40">${option.name}</span>
            `).join('')
            : '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderFavoriteColorSelection();

        ['hobbies', 'games', 'interest'].forEach(field => {
            profilePreferences[field] = JSON.parse(document.getElementById(`f-${field}`).value || '[]');
            renderPreferenceChips(field);
        });
    });
</script>
@endpush
