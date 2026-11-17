@extends('fsrapp.layouts.app')
@section('content')
    @include('fsrapp.layouts.header')

    <main class="pt-20 pb-28 px-4 max-w-md mx-auto min-h-screen" id="profile-main" data-mode="view">

        <!-- ===== Laravel Session Alerts ===== -->
        @if (session('success'))
            <div
                class="mb-4 p-3.5 rounded-xl bg-[#00E5A0]/20 border border-[#00E5A0]/50 text-[#00E5A0] text-xs font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-4 p-3.5 rounded-xl bg-red-950/80 border border-red-500/30 text-red-200 text-xs font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">error</span>
                {{ session('error') }}
            </div>
        @endif

        <!-- ===== Profile Header Card ===== -->
        <div class="glass-card rounded-2xl mb-6 overflow-hidden border border-white/10 bg-white/[0.02]">
            <div class="pf-cover"></div>
            <div class="flex flex-col items-center text-center px-6 -mt-14 pb-6">
                <div class="pf-halo">
                    <div class="pf-avatar-ring">
                        <div class="pf-avatar-inner">
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" class="w-full h-full object-cover">
                            @else
                                <div
                                    class="w-full h-full rounded-full bg-gradient-to-tr from-[#7c3aed] to-[#d946ef] flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-4xl">person</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <h3 class="font-display text-xl font-extrabold text-[#e8ddff] mt-3" id="display-name">
                    {{ $user->name ?: __('app.player_placeholder') }}</h3>
                <div class="flex items-center gap-1.5 mt-1 text-white/50 text-xs" id="display-location">
                    <span class="material-symbols-outlined text-[16px] text-[#d2bbff]">location_on</span>
                    {{ is_array($user->city) ? implode(', ', $user->city) : $user->city }}{{ $user->city && $user->state ? ', ' : '' }}{{ is_array($user->state) ? implode(', ', $user->state) : $user->state }}
                </div>
                @if ($user->referral_code)
                    <button id="pf-copy-referral" onclick="copyReferralCode('{{ $user->referral_code }}')"
                        class="pf-ref-pill pf-neon-glow mt-4 flex items-center gap-2 bg-white/5 border border-fuchsia-400/40 rounded-full px-4 py-1.5 active:scale-95 transition-transform">
                        <span class="material-symbols-outlined text-[#ffd76a] text-lg">card_giftcard</span>
                        <span class="font-mono text-[11px] font-bold text-[#e8ddff]">CODE: {{ $user->referral_code }}</span>
                        <span class="material-symbols-outlined text-white/50 text-[16px]">content_copy</span>
                        <!-- Perfectly Rounded WhatsApp Button -->
                        <span onclick="openWhatsAppModal('{{ $user->referral_code }}', '{{ $user->mobile }}')"
                            class="bg-[#25D366] hover:bg-[#1ebe5d] text-white w-7 h-7 rounded-full transition-all active:scale-95 flex items-center justify-center cursor-pointer shadow-md shadow-green-500/20"
                            title="Share on WhatsApp">

                            <!-- ✅ Icon size 16x16 kar diya hai (content_copy ke barabar) -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path
                                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                            </svg>
                        </span>
                    </button>
                @endif
                <p class="text-xs text-white/50 mt-3.5 leading-relaxed">
                    <i>{{ __('app.invite_friends_earn_points_desc') }}</i>
                </p>
                <button style="    max-width: 215px;" type="button" onclick="openProfileEditModal()"
                    class="w-full mt-4 bg-gradient-to-r from-[#7c3aed] to-[#d946ef] text-white py-3 rounded-full font-bold text-xs flex items-center justify-center gap-2 active:scale-95 transition-transform shadow-lg shadow-[#7c3aed]/25">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    {{ __('app.update_profile') }}
                </button>
            </div>
        </div>

        <!-- ===== Whatsup: user whatsup subscribe Component ===== -->
        @include('fsrapp.home.components.user-whatsapp-subscribe')

        <!-- ===== Stats Grid Component ===== -->
        @include('fsrapp.home.components.users_account_info')


        <!-- ===== Profile Fields Content ===== -->
        <div id="profile-fields">
            <!-- View Mode -->
            <div id="mode-view">
                <div class="flex flex-col gap-3 mb-6">
                    <p class="pf-section-label">{{ __('app.contact_verification') }}</p>

                    {{-- <div class="pf-contact-row">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="material-symbols-outlined text-[#d2bbff] text-[20px] shrink-0">mail</span>
                            <div class="min-w-0">
                                <p class="font-mono text-[9px] text-white/40 tracking-wider">EMAIL</p>
                                <div class="text-[#e8ddff] text-xs font-semibold mt-0.5 truncate" id="view-email">
                                    {{ $user->email ?? '—' }}</div>
                            </div>
                        </div>
                        <div id="view-verify-email">
                            @if ($user->verify_email)
                                <span class="pf-verified-pill"><span class="material-symbols-outlined text-[14px]"
                                    style="font-variation-settings:'FILL' 1;">verified</span>{{ __('app.verified') }}</span>
                            @else
                                <button type="button" onclick="verifyEmail()" class="pf-unverified-pill"><span
                                        class="material-symbols-outlined text-[13px]">error</span>Verify now</button>
                            @endif
                        </div>
                    </div> --}}

                    <div class="pf-contact-row">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="material-symbols-outlined text-[#d2bbff] text-[20px] shrink-0">call</span>
                            <div class="min-w-0">
                                <p class="font-mono text-[9px] text-white/40 tracking-wider">{{ strtoupper(__('app.mobile_number')) }}</p>
                                <div class="text-[#e8ddff] text-xs font-semibold mt-0.5 truncate" id="view-mobile">
                                    {{ $user->mobile ?? '—' }}</div>
                            </div>
                        </div>
                        <div id="view-verify-mobile">
                            @if ($user->verify_mobile)
                                <span class="pf-verified-pill"><span class="material-symbols-outlined text-[14px]"
                                    style="font-variation-settings:'FILL' 1;">verified</span>{{ __('app.verified') }}</span>
                            @else
                                <button type="button" onclick="verifyMobile()" class="pf-unverified-pill"><span
                                        class="material-symbols-outlined text-[13px]">error</span>{{ __('app.verify_now') }}</button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-2xl p-5 flex flex-col gap-4 mb-6 border border-white/10 bg-white/[0.02]">
                    <p class="pf-section-label">{{ __('app.personal_details') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="font-mono text-[10px] text-white/40 tracking-wider">{{ strtoupper(__('app.gender')) }}</p>
                            <p class="text-[#e8ddff] text-xs font-semibold mt-0.5 capitalize" id="view-gender">
                                {{ $user->gender ? __('app.' . strtolower($user->gender)) : '—' }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] text-white/40 tracking-wider">{{ strtoupper(__('app.date_of_birth')) }}</p>
                            <p class="text-[#e8ddff] text-xs font-semibold mt-0.5" id="view-dob">
                                {{ $user->date_of_birth ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] text-white/40 tracking-wider">{{ strtoupper(__('app.state')) }}</p>
                            <p class="text-[#e8ddff] text-xs font-semibold mt-0.5" id="view-state">
                                {{ $user->state ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] text-white/40 tracking-wider">{{ strtoupper(__('app.city')) }}</p>
                            <p class="text-[#e8ddff] text-xs font-semibold mt-0.5" id="view-city">
                                {{ $user->city ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] text-white/40 tracking-wider">{{ strtoupper(__('app.alt_mobile')) }}</p>
                            <p class="text-[#e8ddff] text-xs font-semibold mt-0.5" id="view-alt_mobile">
                                {{ $user->alternative_mobile ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] text-white/40 tracking-wider">{{ strtoupper(__('app.player_name')) }}</p>
                            <p class="text-[#e8ddff] text-xs font-semibold mt-0.5" id="view-player-name">
                                {{ $user->player_name ?? '—' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="font-mono text-[10px] text-white/40 tracking-wider">{{ strtoupper(__('app.address')) }}</p>
                        <p class="text-[#e8ddff] text-xs font-semibold mt-0.5" id="view-address">
                            {{ $user->address ?? '—' }}</p>
                    </div>
                </div>

                @php
                    $preferenceNames = function ($value) {
                        if (is_null($value) || $value === '' || $value === '[]') {
                            return [];
                        }

                        $items = is_array($value) ? $value : json_decode((string) $value, true);

                        if (!is_array($items)) {
                            $items = preg_split('/\s*,\s*/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
                        }

                        return collect($items)
                            ->map(function ($item) {
                                if (is_array($item)) {
                                    $text = trim((string) ($item['name'] ?? ''));
                                    return $text === '[]' ? '' : $text;
                                }

                                $text = trim((string) $item);
                                return $text === '[]' ? '' : $text;
                            })
                            ->filter(fn($item) => $item !== '' && $item !== '[]')
                            ->values()
                            ->all();
                    };

                    $favoriteColorNames = $preferenceNames($user->favorite_color);
                    $hobbies = $preferenceNames($user->hobbies);
                    $games = $preferenceNames($user->games);
                    $interest = $preferenceNames($user->interest);

                    $tagSections = [
                        [
                            'title' => __('app.hobbies'),
                            'items' => $hobbies,
                            'icon' => 'auto_awesome',
                            'class' => 'bg-[#7c3aed]/20 text-[#d2bbff] border-[#7c3aed]/40',
                        ],
                        [
                            'title' => __('app.games'),
                            'items' => $games,
                            'icon' => 'sports_esports',
                            'class' => 'bg-[#00E5A0]/20 text-[#00E5A0] border-[#00E5A0]/40',
                        ],
                        [
                            'title' => __('app.interests'),
                            'items' => $interest,
                            'icon' => 'favorite',
                            'class' => 'bg-[#ffd76a]/20 text-[#ffd76a] border-[#ffd76a]/40',
                        ],
                        [
                            'title' => __('app.favorite_color'),
                            'items' => $favoriteColorNames,
                            'icon' => 'palette',
                            'class' => 'bg-[#7c3aed]/20 text-[#d2bbff] border-[#7c3aed]/40',
                        ],
                    ];
                @endphp

                <div id="view-tags">
                    @foreach ($tagSections as $section)
                        <div class="mb-4">
                            <p class="pf-section-label">
                                <span class="material-symbols-outlined text-[13px]">{{ $section['icon'] }}</span>
                                {{ $section['title'] }}
                            </p>

                            @if (!empty($section['items']))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($section['items'] as $item)
                                        @if ($item)
                                            <span class="pf-chip {{ $section['class'] }}">
                                                {{ $item }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="text-[#e8ddff] text-xs font-semibold mt-1">-</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @include('fsrapp.profile.components.edit-modal')
        </div>

        <!-- ===== Quick Navigation Links Card ===== -->
        <div
            class="glass-card rounded-2xl border border-white/10 bg-white/[0.02] divide-y divide-white/10 mb-5 overflow-hidden">
            <a href="{{ route('fsrapp.history') }}"
                class="w-full flex items-center justify-between px-5 py-4 hover:bg-white/[0.05] active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#d2bbff] text-xl">history</span>
                    <span class="text-xs font-bold text-[#e8ddff]">{{ __('app.quiz_history') }}</span>
                </span>
                <span class="material-symbols-outlined text-white/40 text-lg">chevron_right</span>
            </a>
            <a href="{{ route('fsrapp.wallet.index') }}"
                class="w-full flex items-center justify-between px-5 py-4 hover:bg-white/[0.05] active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#ffd76a] text-xl">account_balance_wallet</span>
                    <span class="text-xs font-bold text-[#e8ddff]">{{ __('app.points') }}</span>
                </span>
                <span class="material-symbols-outlined text-white/40 text-lg">chevron_right</span>
            </a>
            <a href="{{ route('fsrapp.referral') }}"
                class="w-full flex items-center justify-between px-5 py-4 hover:bg-white/[0.05] active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#00E5A0] text-xl">share</span>
                    <span class="text-xs font-bold text-[#e8ddff]">{{ __('app.refer_earn') }}</span>
                </span>
                <span class="material-symbols-outlined text-white/40 text-lg">chevron_right</span>
            </a>
            <a href="{{ route('fsrapp.feedback.history') }}"
                class="w-full flex items-center justify-between px-5 py-4 hover:bg-white/[0.05] active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3">
                    <!-- Icon (Cyan color for History, aap isse #00E5A0 bhi kar sakte hain agar same color chahiye) -->
                    <span class="material-symbols-outlined text-[#38bdf8] text-xl">history</span>
                    <span class="text-xs font-bold text-[#e8ddff]">{{ __('app.feedback_history') }}</span>
                </span>
                <!-- Right Arrow Icon -->
                <span class="material-symbols-outlined text-white/40 text-lg">chevron_right</span>
            </a>
            <a href="{{ route('fsrapp.privacy-policy') }}"
                class="w-full flex items-center justify-between px-5 py-4 hover:bg-white/[0.05] active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#818cf8] text-xl">shield</span>
                    <span class="text-xs font-bold text-[#e8ddff]">{{ __('app.privacy_policy') }}</span>
                </span>
                <span class="material-symbols-outlined text-white/40 text-lg">chevron_right</span>
            </a>
            <a href="{{ route('fsrapp.terms-conditions') }}"
                class="w-full flex items-center justify-between px-5 py-4 hover:bg-white/[0.05] active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#fbbf24] text-xl">description</span>
                    <span class="text-xs font-bold text-[#e8ddff]">{{ __('app.terms_conditions') }}</span>
                </span>
                <span class="material-symbols-outlined text-white/40 text-lg">chevron_right</span>
            </a>
            <a href="{{ route('fsrapp.delete-account') }}"
                class="w-full flex items-center justify-between px-5 py-4 hover:bg-white/[0.05] active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#f87171] text-xl">person_remove</span>
                    <span class="text-xs font-bold text-[#e8ddff]">{{ __('app.delete_account') }}</span>
                </span>
                <span class="material-symbols-outlined text-white/40 text-lg">chevron_right</span>
            </a>
        </div>

        <!-- ===== Logout Card ===== -->
        <div class="pf-danger-card rounded-2xl p-1.5 mb-6">
            <form action="{{ route('fsrapp.logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="w-full py-3 rounded-xl text-red-400 font-bold text-xs active:scale-95 transition-all flex items-center justify-center gap-2 hover:bg-red-500/10">
                    <span class="material-symbols-outlined text-[18px]">logout</span>{{ __('app.logout') }}
                </button>
            </form>
        </div>



        <!-- ===== Toast Notification Container ===== -->
        <div id="toast" class="fixed top-4 left-1/2 z-[100] hidden max-w-[90%] toast-enter"></div>

    </main>
    @include('fsrapp.layouts.bottommenu')
@endsection

@push('scripts')
    <script>
        function openProfileEditModal() {
            const modal = document.getElementById('mode-edit');
            if (!modal) return;
            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
            if (typeof window.ensureCitiesLoaded === 'function') {
                window.ensureCitiesLoaded();
            }
        }

        function closeProfileEditModal() {
            const modal = document.getElementById('mode-edit');
            if (!modal) return;
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
        }

        function copyReferralCode(code) {
            if (!code) return;
            navigator.clipboard?.writeText(code)
                .then(() => showToast(@json(__('app.copy_success')), 'success'))
                .catch(() => showToast(@json(__('app.copy_failed')), 'error'));
        }

        function showToast(msg, type = 'info') {
            const box = document.getElementById('toast');
            if (!box) return;

            const colors = {
                info: 'bg-[#160d2f] border-white/10 text-[#e8ddff]',
                error: 'bg-red-950/80 border-red-500/30 text-red-200',
                success: 'bg-[#00E5A0]/20 border-[#00E5A0]/50 text-[#00E5A0]'
            };

            box.className =
                `fixed top-4 left-1/2 -translate-x-1/2 z-[100] toast-enter max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg text-xs font-semibold ${colors[type] || colors.info}`;
            box.textContent = msg;
            box.classList.remove('hidden');
            setTimeout(() => box.classList.add('hidden'), 3200);
        }

        function verifyMobile() {
            fetch("{{ route('fsrapp.profile.verify-mobile') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        showToast('Mobile verified successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const stateSelect = document.getElementById('f-state');
            const citySelect = document.getElementById('f-city');
            const currentCity = @json($user->city);

            if (stateSelect && citySelect) {
                function loadCities(stateId, selectedCity = null) {
                    if (!stateId) {
                        citySelect.innerHTML = '<option value="">Select city</option>';
                        return;
                    }

                    citySelect.innerHTML = '<option value="">Loading cities...</option>';

                    fetch("{{ route('fsrapp.profile.cities') }}?state_id=" + stateId, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            citySelect.innerHTML = '<option value="">Select city</option>';

                            if (data.success && data.cities.length > 0) {
                                data.cities.forEach((city) => {
                                    const option = document.createElement('option');
                                    option.value = city.name;
                                    option.textContent = city.name;
                                    if (selectedCity && city.name === selectedCity) {
                                        option.selected = true;
                                    }
                                    citySelect.appendChild(option);
                                });
                            } else {
                                citySelect.innerHTML = '<option value="">No cities found</option>';
                            }
                        })
                        .catch(() => {
                            citySelect.innerHTML = '<option value="">Unable to load cities</option>';
                        });
                }

                stateSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    loadCities(selectedOption?.dataset?.stateId);
                });

                let citiesLoaded = false;
                window.ensureCitiesLoaded = function() {
                    if (!citiesLoaded && stateSelect.value) {
                        const selectedOption = stateSelect.options[stateSelect.selectedIndex];
                        const stateId = selectedOption?.dataset?.stateId;
                        if (stateId) {
                            loadCities(stateId, currentCity);
                            citiesLoaded = true;
                        }
                    }
                };
            }

            const photoBtn = document.getElementById('pf-photo-btn');
            const photoInput = document.getElementById('pf-photo-input');
            const photoPreview = document.getElementById('pf-photo-preview');

            if (photoBtn && photoInput) {
                photoBtn.onclick = () => photoInput.click();

                photoInput.onchange = () => {
                    const file = photoInput.files[0];
                    if (!file) return;

                    if (!file.type.startsWith('image/')) {
                        showToast('Please select a valid image.', 'error');
                        photoInput.value = '';
                        return;
                    }

                    // if (file.size > 10 * 1024 * 1024) {
                    //     showToast('Image size should not exceed 10 MB.', 'error');
                    //     photoInput.value = '';
                    //     return;
                    // }

                    if (file.size > 1 * 1024 * 1024) {
                        showToast(
                            'Please upload an image up to 1 MB. Allowed formats: JPG, JPEG, PNG, WEBP.',
                            'error',
                            6000
                        );

                        photoInput.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = () => {
                        if (photoPreview) {
                            photoPreview.innerHTML =
                                `<img src="${reader.result}" class="w-full h-full object-cover" alt="Profile Photo">`;
                        }
                    };
                    reader.readAsDataURL(file);
                };
            }
        });
    </script>
@endpush
