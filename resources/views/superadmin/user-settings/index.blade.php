@extends('superadmin.layouts.app')
@section('title', 'User Settings')

@section('content')
    @push('styles')
        <style>
            .user-settings-page {
                width: 100%;
            }

            .user-settings-head {
                margin-bottom: 20px;
            }

            .user-settings-head h1 {
                margin: 0;
                color: var(--ink);
                font-size: 26px;
                font-weight: 700;
            }

            .user-settings-head p {
                margin: 6px 0 0;
                color: var(--ink-faint);
                font-size: 13px;
            }

            .user-settings-layout {
                display: flex;
                align-items: flex-start;
                gap: 20px;
            }

            .user-settings-tabs {
                width: 240px;
                min-width: 240px;
                padding: 10px;
                border: 1px solid var(--line);
                border-radius: 14px;
                background: var(--surface);
            }

            .user-settings-tabs a {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 6px;
                padding: 13px 14px;
                border-radius: 10px;
                color: var(--ink);
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                transition: background .2s ease, color .2s ease;
            }

            .user-settings-tabs a:last-child {
                margin-bottom: 0;
            }

            .user-settings-tabs a:hover,
            .user-settings-tabs a.active {
                background: var(--indigo);
                color: #fff;
            }

            .user-settings-content {
                flex: 1;
                min-width: 0;
            }

            .user-settings-card {
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 14px;
                background: var(--surface);
            }

            .user-settings-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 20px 22px;
                border-bottom: 1px solid var(--line);
            }

            .user-settings-card-head h2 {
                margin: 0;
                color: var(--ink);
                font-size: 18px;
                font-weight: 700;
            }

            .user-settings-card-head p {
                margin: 5px 0 0;
                color: var(--ink-faint);
                font-size: 12px;
            }

            .user-settings-toolbar {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 14px 22px;
                border-bottom: 1px solid var(--line);
                background: var(--surface);
            }

            .user-settings-search {
                flex: 1;
                min-width: 0;
                max-width: 420px;
                border: 1px solid var(--line);
                border-radius: 9px;
                padding: 10px 12px;
                background: var(--surface);
                color: var(--ink);
                font: inherit;
                font-size: 13px;
            }

            .user-settings-search:focus {
                border-color: var(--indigo);
                outline: 2px solid rgba(79, 70, 229, .12);
            }

            .user-settings-select {
                border: 1px solid var(--line);
                border-radius: 9px;
                padding: 10px 12px;
                background: var(--surface);
                color: var(--ink);
                font: inherit;
                font-size: 13px;
                min-width: 170px;
                max-width: 220px;
            }

            .user-settings-select:focus {
                border-color: var(--indigo);
                outline: 2px solid rgba(79, 70, 229, .12);
            }

            .user-settings-badge-sub {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 6px;
                background: #f1f5f9;
                color: #475569;
                font-size: 12px;
                font-weight: 600;
            }

            .user-settings-search-button,
            .user-settings-reset {
                flex: 0 0 auto;
                border: 1px solid var(--line);
                border-radius: 9px;
                padding: 9px 13px;
                background: var(--surface);
                color: var(--ink);
                font: inherit;
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
            }

            .user-settings-search-button {
                border-color: var(--indigo);
                background: var(--indigo);
                color: #fff;
            }

            .user-settings-add {
                flex: 0 0 auto;
                border: 0;
                border-radius: 9px;
                padding: 10px 16px;
                background: var(--indigo);
                color: #fff;
                font: inherit;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .user-settings-table-wrap {
                overflow-x: auto;
            }

            .user-settings-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
            }

            .user-settings-table th,
            .user-settings-table td {
                padding: 14px 22px;
                border-bottom: 1px solid var(--line);
            }

            .user-settings-table th {
                background: var(--paper);
                color: var(--ink-faint);
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
            }

            .user-settings-table td {
                color: var(--ink);
                font-size: 13px;
            }

            .user-settings-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .user-settings-status {
                display: inline-block;
                border-radius: 999px;
                padding: 4px 9px;
                background: var(--emerald-bg);
                color: #047857;
                font-size: 11px;
                font-weight: 700;
            }

            .user-settings-status.inactive {
                background: var(--coral-bg);
                color: #b91c1c;
            }

            .user-settings-edit {
                display: inline-block;
                border: 1px solid var(--line);
                border-radius: 8px;
                padding: 7px 10px;
                color: var(--indigo);
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
            }

            .user-settings-empty {
                padding: 36px 20px !important;
                color: var(--ink-faint) !important;
                text-align: center;
            }

            .user-settings-visually-hidden {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                clip-path: inset(50%);
            }

            .user-settings-alert {
                margin-bottom: 14px;
                border-radius: 9px;
                padding: 12px 15px;
                background: var(--emerald-bg);
                color: #047857;
            }

            .user-settings-errors {
                margin-bottom: 14px;
                border: 1px solid #fecaca;
                border-radius: 9px;
                padding: 12px 15px;
                background: var(--coral-bg);
                color: #b91c1c;
            }

            .user-settings-modal {
                position: fixed;
                z-index: 9999;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .user-settings-modal.active {
                display: flex;
            }

            .user-settings-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, .65);
                backdrop-filter: blur(4px);
            }

            .user-settings-modal-card {
                position: relative;
                width: 100%;
                max-width: 480px;
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 22px;
                background: var(--surface);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
            }

            .user-settings-modal-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 20px;
                padding-bottom: 14px;
                border-bottom: 1px solid var(--line);
            }

            .user-settings-modal-head h3 {
                margin: 0;
                color: var(--ink);
                font-size: 18px;
            }

            .user-settings-close {
                border: 0;
                background: transparent;
                color: var(--ink-faint);
                font-size: 20px;
                cursor: pointer;
            }

            .user-settings-field {
                margin-bottom: 16px;
            }

            .user-settings-field label {
                display: block;
                margin-bottom: 7px;
                color: var(--ink);
                font-size: 13px;
                font-weight: 600;
            }

            .user-settings-field input,
            .user-settings-field select {
                width: 100%;
                border: 1px solid var(--line);
                border-radius: 9px;
                padding: 10px 12px;
                background: var(--surface);
                color: var(--ink);
                font: inherit;
            }

            .user-settings-submit {
                width: 100%;
                border: 0;
                border-radius: 9px;
                padding: 11px 16px;
                background: var(--indigo);
                color: #fff;
                font: inherit;
                font-weight: 600;
                cursor: pointer;
            }

            .user-settings-field-error {
                display: block;
                margin-top: 5px;
                color: #dc3545;
                font-size: 11px;
            }

            @media (max-width: 700px) {
                .user-settings-layout {
                    flex-direction: column;
                }

                .user-settings-tabs {
                    display: flex;
                    width: 100%;
                    min-width: 0;
                    gap: 6px;
                    overflow-x: auto;
                }

                .user-settings-tabs a {
                    flex: 0 0 auto;
                    margin: 0;
                    white-space: nowrap;
                }

                .user-settings-content {
                    width: 100%;
                }

                .user-settings-card-head {
                    align-items: flex-start;
                    padding: 16px;
                }

                .user-settings-table th,
                .user-settings-table td {
                    padding: 12px 14px;
                }

                .user-settings-toolbar {
                    padding: 12px 14px;
                }
            }

            @media (max-width: 480px) {
                .user-settings-head h1 {
                    font-size: 22px;
                }

                .user-settings-card-head {
                    flex-direction: column;
                }

                .user-settings-add {
                    width: 100%;
                }

                .user-settings-toolbar {
                    flex-wrap: wrap;
                }

                .user-settings-search {
                    flex-basis: 100%;
                    max-width: none;
                }

                .user-settings-select {
                    flex-basis: 100%;
                    max-width: none;
                }
            }
        </style>
    @endpush

    <div class="user-settings-page">
        <div class="user-settings-head">
            <h1>User Settings</h1>
            <p>Manage hobbies, games, interests, colors, states, cities, SIM networks and electricity zones.</p>
        </div>

        @if (session('success'))
            <div class="user-settings-alert" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="user-settings-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="user-settings-layout">
            <nav class="user-settings-tabs" aria-label="User setting categories">
                @foreach ([
                    'hobby' => ['Hobbies', '🎨'],
                    'game' => ['Games', '🎮'],
                    'interest' => ['Interests', '✨'],
                    'color' => ['Colors', '🌈'],
                    'states' => ['States', '🗺️'],
                    'cities' => ['Cities', '🏙️'],
                    'sim_networks' => ['SIM Networks', '📶'],
                    'electricity_zones' => ['Electricity Zones', '⚡'],
                ] as $tab => $tabDetails)
                    <a href="{{ route('superadmin.user-settings.index', ['type' => $tab]) }}"
                        class="{{ $type === $tab ? 'active' : '' }}"
                        @if ($type === $tab) aria-current="page" @endif>
                        <span aria-hidden="true">{{ $tabDetails[1] }}</span>{{ $tabDetails[0] }}
                    </a>
                @endforeach
            </nav>

            <section class="user-settings-content">
                @if ($type === 'states')
                    @include('superadmin.user-settings.partials.states-content')
                @elseif ($type === 'cities')
                    @include('superadmin.user-settings.partials.cities-content')
                @elseif ($type === 'sim_networks')
                    @include('superadmin.user-settings.partials.sim-networks-content')
                @elseif ($type === 'electricity_zones')
                    @include('superadmin.user-settings.partials.electricity-zones-content')
                @else
                    @include('superadmin.user-settings.partials.default-content')
                @endif
            </section>
        </div>
    </div>

    <script>
        function openUserSettingModal() {
            var modal = document.getElementById('userSettingModal');
            if (modal) {
                modal.classList.add('active');
                var firstInput = modal.querySelector('input:not([type=hidden]):not([readonly]), select');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        }

        function closeUserSettingModal() {
            var modal = document.getElementById('userSettingModal');
            if (modal) {
                modal.classList.remove('active');
            }
            if (@json($editing !== null)) {
                var url = new URL("{{ route('superadmin.user-settings.index', ['type' => $type]) }}", window.location.origin);
                var params = new URLSearchParams(window.location.search);
                if (params.get('search')) url.searchParams.set('search', params.get('search'));
                if (params.get('state_id')) url.searchParams.set('state_id', params.get('state_id'));
                window.location.href = url.toString();
            }
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeUserSettingModal();
            }
        });
    </script>
@endsection
