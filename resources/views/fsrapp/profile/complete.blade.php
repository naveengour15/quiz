@extends('fsrapp.layouts.app')
@section('content')
    <div class="glow-field">
        <div class="glow-orb o1"></div>
        <div class="glow-orb o2"></div>
        <div class="glow-orb o3"></div>
    </div>

    <div class="max-w-xl mx-auto min-h-screen px-4 py-8 relative z-10 flex flex-col justify-center">

        <!-- Header -->
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="pf-neon-ring w-18 h-18 mb-4">
                <div class="w-full h-full rounded-full overflow-hidden bg-[#1e1638] flex items-center justify-center shadow-[0_0_24px_rgba(217,70,239,0.35)]">
                    <span class="material-symbols-outlined text-[32px]" style="color:#e879f9;">badge</span>
                </div>
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-[#e8ddff]">{{ __('app.complete_profile') }}</h1>
            <p class="text-[13.5px] font-medium text-[#c9b6ff]/75 mt-1">{{ __('app.profile_intro') }}</p>
        </div>

        <!-- Notifications -->
        @if (session('error'))
            <div class="mb-4 p-3.5 bg-red-500/15 border border-red-500/30 text-red-300 rounded-xl text-center font-medium text-sm backdrop-blur-md">
                {{ session('error') }}
            </div>
        @endif
        @if (session('info'))
            <div class="mb-4 p-3.5 bg-purple-500/15 border border-purple-500/30 text-purple-300 rounded-xl text-center font-medium text-sm backdrop-blur-md">
                {{ session('info') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3.5 bg-red-500/15 border border-red-500/30 text-red-300 rounded-xl text-center font-medium text-sm backdrop-blur-md">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Form Card -->
        <form id="cp-form" action="{{ route('fsrapp.save-profile') }}" method="POST"  
            class="rounded-3xl p-5 sm:p-7 flex flex-col gap-4 relative overflow-hidden"
            style="background: linear-gradient(160deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02)); backdrop-filter: blur(16px); border: 1px solid rgba(196, 158, 255, 0.2); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);">
            @csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            @if (!empty($redirectParam))
                <input type="hidden" name="redirect" value="{{ $redirectParam }}">
            @endif

            <!-- Full Name -->
            <div>
                <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-1.5 block">{{ strtoupper(__('app.full_name')) }} *</label>
                <input id="f-name" name="name" type="text" placeholder="{{ __('app.your_full_name') }}" value="{{ old('name', $user->name ?? '') }}" required minlength="3"
                    pattern="[a-zA-Z\s]+" title="Only alphabets and spaces are allowed"
                    oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                    onkeydown="if(!event.ctrlKey && !event.altKey && !event.metaKey && ![8,9,13,27,46,37,38,39,40].includes(event.keyCode) && !/^[a-zA-Z\s]$/.test(event.key)){event.preventDefault();}"
                    style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(196, 158, 255, 0.2); outline: none; box-shadow: none;"
                    class="w-full rounded-2xl px-4 py-3.5 text-[#f3edff] placeholder:text-[#c9b6ff]/35 font-medium text-base focus:border-[#d946ef] focus:shadow-[0_0_18px_rgba(217,70,239,0.3)] transition-all">
            
                 @error('name')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Referral Code -->
            <div>
                <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-1.5 block">{{ strtoupper(__('app.referral_code')) }} *</label>
                <input id="f-referral" name="referral_code" type="text" placeholder="e.g. NAV1A8K2QX" required
                    value="{{ old('referral_code', $user->referral_code_to ?? $referralCode) }}" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(196, 158, 255, 0.2); outline: none; box-shadow: none;"
                    class="w-full rounded-2xl px-4 py-3.5 text-[#f3edff] placeholder:text-[#c9b6ff]/35 font-mono text-base font-semibold focus:border-[#d946ef] focus:shadow-[0_0_18px_rgba(217,70,239,0.3)] transition-all">
              
                 <p style="text-align: center; margin-top: 10px;">{{ __('app.no_referral_code') }}<br><span style="color:#eead01; font-weight: bold";>SANDE687</span></p>
                 @error('referral_code')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
           </div>

            <!-- State & City -->
            {{-- <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-1.5 block">STATE </label>
                    @php
                        $selectedState = old('state', $user->state ?? '');
                    @endphp
                    <select id="f-state" name="state"
                        style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(196, 158, 255, 0.2); outline: none; box-shadow: none;"
                        class="w-full rounded-2xl px-4 py-3.5 text-[#f3edff] font-medium text-base focus:border-[#d946ef] focus:shadow-[0_0_18px_rgba(217,70,239,0.3)] transition-all">
                        <option value="" class="bg-[#1e1638] text-[#c9b6ff]">Select state</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" class="bg-[#1e1638] text-[#f3edff]"
                                data-state-name="{{ $state->name }}" {{ $selectedState == $state->id || $selectedState == $state->name ? 'selected' : '' }}>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-1.5 block">CITY </label>
                    @php
                        $selectedCity = old('city', $user->city ?? '');
                    @endphp
                    <select id="f-city" name="city"
                        style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(196, 158, 255, 0.2); outline: none; box-shadow: none;"
                        class="w-full rounded-2xl px-4 py-3.5 text-[#f3edff] font-medium text-base focus:border-[#d946ef] focus:shadow-[0_0_18px_rgba(217,70,239,0.3)] transition-all">
                        <option value="" class="bg-[#1e1638] text-[#c9b6ff]">Select city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->name }}" class="bg-[#1e1638] text-[#f3edff]" data-state-id="{{ $city->state_id }}"
                                {{ $selectedCity == $city->name ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Date of Birth & Gender -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-1.5 block">DATE OF BIRTH </label>
                    @php
                        $dobVal = old(
                            'dob',
                            isset($user->date_of_birth) && $user->date_of_birth
                                ? \Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d')
                                : '',
                        );
                    @endphp
                    <input id="f-dob" name="dob" type="date" value="{{ $dobVal }}" 
                        style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(196, 158, 255, 0.2); outline: none; box-shadow: none; color-scheme: dark;"
                        class="w-full rounded-2xl px-4 py-3.5 text-[#f3edff] font-medium text-base focus:border-[#d946ef] focus:shadow-[0_0_18px_rgba(217,70,239,0.3)] transition-all">
                </div>
                <div>
                    <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-1.5 block">GENDER </label>
                    @php
                        $selectedGender = old('gender', $user->gender ?? '');
                    @endphp
                    <select id="f-gender" name="gender"
                        style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(196, 158, 255, 0.2); outline: none; box-shadow: none;"
                        class="w-full rounded-2xl px-4 py-3.5 text-[#f3edff] font-medium text-base focus:border-[#d946ef] focus:shadow-[0_0_18px_rgba(217,70,239,0.3)] transition-all">
                        <option value="" class="bg-[#1e1638] text-[#c9b6ff]">Select gender</option>
                        <option value="male" {{ $selectedGender == 'male' ? 'selected' : '' }} class="bg-[#1e1638] text-[#f3edff]">Male</option>
                        <option value="female" {{ $selectedGender == 'female' ? 'selected' : '' }} class="bg-[#1e1638] text-[#f3edff]">Female</option>
                        <option value="other" {{ $selectedGender == 'other' ? 'selected' : '' }} class="bg-[#1e1638] text-[#f3edff]">Other</option>
                    </select>
                </div>
            </div> --}}

            <!-- Language -->
            <div>
                <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-2 block"> {{ strtoupper(__('app.language')) }} </label>

                @php
                    $selectedLanguage = old('language', $user->language ?? 'en');
                @endphp

                <div class="grid grid-cols-3 gap-3">

                    <!-- English -->
                    <label class="relative cursor-pointer">
                        <input
                            type="radio"
                            name="language"
                            value="en"
                            class="peer sr-only"
                            {{ $selectedLanguage === 'en' ? 'checked' : '' }}
                        >

                        <div class="rounded-2xl px-4 py-3.5 text-center font-semibold
                                    text-[#f3edff] transition-all
                                    border border-[rgba(196,158,255,0.2)]
                                    bg-[rgba(255,255,255,0.04)]
                                    peer-checked:border-[#d946ef]
                                    peer-checked:bg-[rgba(217,70,239,0.15)]
                                    peer-checked:shadow-[0_0_18px_rgba(217,70,239,0.25)]">

                            <span class="material-symbols-outlined align-middle mr-1 text-[20px]">
                                language
                            </span>

                            English
                        </div>
                    </label>

                    <!-- Hindi -->
                    <label class="relative cursor-pointer">
                        <input
                            type="radio"
                            name="language"
                            value="hi"
                            class="peer sr-only"
                            {{ $selectedLanguage === 'hi' ? 'checked' : '' }}
                        >

                        <div class="rounded-2xl px-4 py-3.5 text-center font-semibold
                                    text-[#f3edff] transition-all
                                    border border-[rgba(196,158,255,0.2)]
                                    bg-[rgba(255,255,255,0.04)]
                                    peer-checked:border-[#d946ef]
                                    peer-checked:bg-[rgba(217,70,239,0.15)]
                                    peer-checked:shadow-[0_0_18px_rgba(217,70,239,0.25)]">

                            <span class="material-symbols-outlined align-middle mr-1 text-[20px]">
                                translate
                            </span>

                            हिंदी
                        </div>
                    </label>

                    <!-- Hinglish -->
                    <label class="relative cursor-pointer">
                        <input
                            type="radio"
                            name="language"
                            value="hinglish"
                            class="peer sr-only"
                            {{ $selectedLanguage === 'hinglish' ? 'checked' : '' }}
                        >

                        <div class="rounded-2xl px-3 py-3.5 text-center font-semibold
                                    text-[#f3edff] transition-all
                                    border border-[rgba(196,158,255,0.2)]
                                    bg-[rgba(255,255,255,0.04)]
                                    peer-checked:border-[#d946ef]
                                    peer-checked:bg-[rgba(217,70,239,0.15)]
                                    peer-checked:shadow-[0_0_18px_rgba(217,70,239,0.25)]">
                            <span class="material-symbols-outlined align-middle mr-1 text-[20px]">translate</span>
                            Hinglish
                        </div>
                    </label>

                </div>

                @error('language')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit CTA -->
            <button type="submit"
                style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%); color: #1a0b3d; font-weight: 800; box-shadow: 0 8px 25px rgba(217, 70, 239, 0.4); border: none;"
                class="w-full mt-3 py-4 rounded-2xl font-display text-base tracking-wide flex items-center justify-center gap-2 active:scale-95 transition-transform cursor-pointer">
                <span>{{ __('app.save_continue') }}</span>
                <span class="material-symbols-outlined text-[20px] font-bold">check_circle</span>
            </button>
        </form>
    </div>
@endsection

{{-- @push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const stateSelect = document.getElementById('f-state');
        const citySelect = document.getElementById('f-city');
        const allCities = Array.from(citySelect.options);

        function filterCities() {
            const stateId = stateSelect.value;
            const selectedCity = @json(old('city', $user->city ?? ''));

            citySelect.innerHTML = '';
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select city';
            defaultOption.className = 'bg-[#1e1638] text-[#c9b6ff]';
            citySelect.appendChild(defaultOption);

            if (!stateId) {
                citySelect.value = '';
                return;
            }

            allCities.forEach(function(option) {
                if (!option.value) return;
                if (option.dataset.stateId == stateId) {
                    const newOption = option.cloneNode(true);
                    newOption.className = 'bg-[#1e1638] text-[#f3edff]';
                    if (newOption.value == selectedCity) {
                        newOption.selected = true;
                    }
                    citySelect.appendChild(newOption);
                }
            });
        }

        stateSelect.addEventListener('change', function() {
            citySelect.innerHTML = '<option value="" class="bg-[#1e1638] text-[#c9b6ff]">Select city</option>';
            const stateId = this.value;
            if (!stateId) return;

            allCities.forEach(function(option) {
                if (option.value && option.dataset.stateId == stateId) {
                    const newOpt = option.cloneNode(true);
                    newOpt.className = 'bg-[#1e1638] text-[#f3edff]';
                    citySelect.appendChild(newOpt);
                }
            });
        });

        filterCities();
    });
</script>
@endpush --}}
