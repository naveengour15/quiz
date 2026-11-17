<div class="user-settings-card">
    <div class="user-settings-card-head">
        <div>
            <h2>Electricity Zones</h2>
            <p>{{ $items->total() }} electricity zones configured</p>
        </div>
        <button type="button" class="user-settings-add" onclick="openUserSettingModal()">
            + Add Electricity Zone
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

        <label for="zoneSearch" class="user-settings-visually-hidden">Search electricity zones</label>
        <input id="zoneSearch" class="user-settings-search" type="search" name="search"
            value="{{ request('search') }}" maxlength="255"
            placeholder="Search zone name, code or state...">
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
                    <th>Zone Name</th>
                    <th>Zone Code</th>
                    <th>State</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $items->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $item->zone }}</strong></td>
                        <td>
                            @if (!empty($item->zone_code))
                                <code style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; color: var(--indigo);">
                                    {{ $item->zone_code }}
                                </code>
                            @else
                                <span style="color: var(--ink-faint);">—</span>
                            @endif
                        </td>
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
                        <td colspan="6" class="user-settings-empty">
                            No electricity zones found.
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
                {{ $editing ? 'Edit Electricity Zone' : 'Add Electricity Zone' }}
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
                <label for="zoneStateId">State</label>
                <select id="zoneStateId" name="state_id" required>
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
                <label for="zoneName">Zone Name</label>
                <input id="zoneName" type="text" name="zone" maxlength="191" required
                    value="{{ old('zone', $editing ? $editing->zone : '') }}"
                    placeholder="Enter zone name (e.g. Southern Power Distribution)">
                @error('zone')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="zoneCode">Zone Code (Optional)</label>
                <input id="zoneCode" type="text" name="zone_code" maxlength="191"
                    value="{{ old('zone_code', $editing ? $editing->zone_code : '') }}"
                    placeholder="Enter zone code (e.g. APSPDCL)">
                @error('zone_code')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="zoneStatus">Status</label>
                <select id="zoneStatus" name="status" required>
                    <option value="active" @selected(old('status', $editing ? $editing->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $editing ? $editing->status : 'active') === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="user-settings-submit">
                {{ $editing ? 'Save changes' : 'Add Electricity Zone' }}
            </button>
        </form>
    </div>
</div>
