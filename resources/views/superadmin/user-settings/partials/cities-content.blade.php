<div class="user-settings-card">
    <div class="user-settings-card-head">
        <div>
            <h2>Cities</h2>
            <p>{{ $items->total() }} cities configured</p>
        </div>
        <button type="button" class="user-settings-add" onclick="openUserSettingModal()">
            + Add City
        </button>
    </div>

    <form method="GET" action="{{ route('superadmin.user-settings.index') }}" class="user-settings-toolbar">
        <input type="hidden" name="type" value="{{ $type }}">
        
        <label for="stateFilter" class="user-settings-visually-hidden">Filter by State</label>
        <select id="stateFilter" name="state_id" class="user-settings-select" onchange="this.form.submit()">
            <option value="">All States</option>
            @foreach ($states as $st)
                <option value="{{ $st->id }}" @selected(request('state_id') == $st->id)>
                    {{ $st->name }}
                </option>
            @endforeach
        </select>

        <label for="citySearch" class="user-settings-visually-hidden">Search cities</label>
        <input id="citySearch" class="user-settings-search" type="search" name="search"
            value="{{ request('search') }}" maxlength="255"
            placeholder="Search city or state name...">
        <button type="submit" class="user-settings-search-button">Search</button>

        @if (request()->filled('search') || request()->filled('state_id'))
            <a class="user-settings-reset"
                href="{{ route('superadmin.user-settings.index', ['type' => $type]) }}">Reset</a>
        @endif
    </form>

    <div class="user-settings-table-wrap">
        <table class="user-settings-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>City Name</th>
                    <th>State</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $items->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $item->name }}</strong></td>
                        <td>
                            <span class="user-settings-badge-sub">{{ $item->state->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="user-settings-status {{ $item->status === 'inactive' ? 'inactive' : '' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td>
                            <a class="user-settings-edit"
                                href="{{ route('superadmin.user-settings.index', ['type' => $type, 'edit' => $item->id, 'state_id' => request('state_id'), 'search' => request('search')]) }}">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="user-settings-empty">
                            No cities found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination" style="margin-top: 20px;">
        @include('common.pagination.superadmin_pagination', ['pageData' => $items])
    </div>
</div>

<div id="userSettingModal" class="user-settings-modal {{ $editing || $errors->any() ? 'active' : '' }}">
    <div class="user-settings-backdrop" onclick="closeUserSettingModal()"></div>
    <div class="user-settings-modal-card" role="dialog" aria-modal="true"
        aria-labelledby="userSettingModalTitle">
        <div class="user-settings-modal-head">
            <h3 id="userSettingModalTitle">
                {{ $editing ? 'Edit City' : 'Add City' }}
            </h3>
            <button type="button" class="user-settings-close" aria-label="Close"
                onclick="closeUserSettingModal()">×</button>
        </div>

        <form method="POST"
            action="{{ $editing
                ? route('superadmin.user-settings.update', ['type' => $type, 'id' => $editing->id])
                : route('superadmin.user-settings.store', ['type' => $type]) }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="user-settings-field">
                <label for="cityStateId">State</label>
                <select id="cityStateId" name="state_id" required>
                    <option value="" disabled @selected(!old('state_id', $editing ? $editing->state_id : ''))>-- Select State --</option>
                    @foreach ($states as $st)
                        <option value="{{ $st->id }}" @selected(old('state_id', $editing ? $editing->state_id : '') == $st->id)>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
                @error('state_id')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="cityName">City Name</label>
                <input id="cityName" type="text" name="name" maxlength="191" required
                    value="{{ old('name', $editing ? $editing->name : '') }}"
                    placeholder="Enter city name (e.g. Pune, Jaipur)">
                @error('name')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="cityStatus">Status</label>
                <select id="cityStatus" name="status" required>
                    <option value="active" @selected(old('status', $editing ? $editing->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $editing ? $editing->status : 'active') === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="user-settings-submit">
                {{ $editing ? 'Save changes' : 'Add City' }}
            </button>
        </form>
    </div>
</div>
