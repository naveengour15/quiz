<div class="user-settings-card">
    <div class="user-settings-card-head">
        <div>
            <h2>States</h2>
            <p>{{ $items->total() }} states configured (Default Country Code: 101)</p>
        </div>
        <button type="button" class="user-settings-add" onclick="openUserSettingModal()">
            + Add State
        </button>
    </div>

    <form method="GET" action="{{ route('superadmin.user-settings.index') }}" class="user-settings-toolbar">
        <input type="hidden" name="type" value="{{ $type }}">
        <label for="stateSearch" class="user-settings-visually-hidden">Search states</label>
        <input id="stateSearch" class="user-settings-search" type="search" name="search"
            value="{{ request('search') }}" maxlength="255"
            placeholder="Search state by name...">
        <button type="submit" class="user-settings-search-button">Search</button>
        @if (request()->filled('search'))
            <a class="user-settings-reset"
                href="{{ route('superadmin.user-settings.index', ['type' => $type]) }}">Reset</a>
        @endif
    </form>

    <div class="user-settings-table-wrap">
        <table class="user-settings-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>State Name</th>
                    <th>Country Code</th>
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
                            <span class="user-settings-badge-sub">{{ $item->country_id ?? 101 }}</span>
                        </td>
                        <td>
                            <span class="user-settings-status {{ $item->status === 'inactive' ? 'inactive' : '' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td>
                            <a class="user-settings-edit"
                                href="{{ route('superadmin.user-settings.index', ['type' => $type, 'edit' => $item->id]) }}">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="user-settings-empty">
                            No states found.
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
                {{ $editing ? 'Edit State' : 'Add State' }}
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
                <label for="stateName">State Name</label>
                <input id="stateName" type="text" name="name" maxlength="191" required
                    value="{{ old('name', $editing ? $editing->name : '') }}"
                    placeholder="Enter state name (e.g. Maharashtra, Rajasthan)">
                @error('name')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="countryId">Country Code</label>
                <input id="countryId" type="number" name="country_id"
                    value="{{ old('country_id', $editing ? ($editing->country_id ?? 101) : 101) }}"
                    placeholder="Default: 101" required>
                <small style="color: var(--ink-faint); font-size: 11px;">Default country code is 101</small>
                @error('country_id')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="stateStatus">Status</label>
                <select id="stateStatus" name="status" required>
                    <option value="active" @selected(old('status', $editing ? $editing->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $editing ? $editing->status : 'active') === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="user-settings-submit">
                {{ $editing ? 'Save changes' : 'Add State' }}
            </button>
        </form>
    </div>
</div>
