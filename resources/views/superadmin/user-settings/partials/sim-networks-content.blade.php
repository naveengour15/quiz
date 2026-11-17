<div class="user-settings-card">
    <div class="user-settings-card-head">
        <div>
            <h2>SIM Networks</h2>
            <p>{{ $items->total() }} sim networks configured</p>
        </div>
        <button type="button" class="user-settings-add" onclick="openUserSettingModal()">
            + Add SIM Network
        </button>
    </div>

    <form method="GET" action="{{ route('superadmin.user-settings.index') }}" class="user-settings-toolbar">
        <input type="hidden" name="type" value="{{ $type }}">
        <label for="simSearch" class="user-settings-visually-hidden">Search sim networks</label>
        <input id="simSearch" class="user-settings-search" type="search" name="search"
            value="{{ request('search') }}" maxlength="255"
            placeholder="Search network name or code (e.g. Airtel, JIO)...">
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
                    <th>Network Name</th>
                    <th>Network Code</th>
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
                            <code style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; color: var(--indigo);">
                                {{ $item->code }}
                            </code>
                        </td>
                        <td>
                            <span class="user-settings-status {{ $item->status === 'inactive' ? 'inactive' : '' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td>
                            <a class="user-settings-edit"
                                href="{{ route('superadmin.user-settings.index', ['type' => $type, 'edit' => $item->id, 'search' => request('search')]) }}">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="user-settings-empty">
                            No SIM networks found.
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
                {{ $editing ? 'Edit SIM Network' : 'Add SIM Network' }}
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
                <label for="simName">Network Name</label>
                <input id="simName" type="text" name="name" maxlength="191" required
                    value="{{ old('name', $editing ? $editing->name : '') }}"
                    placeholder="Enter network name (e.g. Airtel, Jio, Vi)">
                @error('name')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="simCode">Network Code</label>
                <input id="simCode" type="text" name="code" maxlength="50" required
                    value="{{ old('code', $editing ? $editing->code : '') }}"
                    placeholder="Enter network code (e.g. AIRTEL, JIO, VI)">
                @error('code')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="simStatus">Status</label>
                <select id="simStatus" name="status" required>
                    <option value="active" @selected(old('status', $editing ? $editing->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $editing ? $editing->status : 'active') === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="user-settings-submit">
                {{ $editing ? 'Save changes' : 'Add SIM Network' }}
            </button>
        </form>
    </div>
</div>
