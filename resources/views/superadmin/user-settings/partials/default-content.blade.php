<div class="user-settings-card">
    <div class="user-settings-card-head">
        <div>
            <h2>{{ $taxonomy['plural'] }}</h2>
            <p>{{ $items->total() }} {{ strtolower($taxonomy['plural']) }} configured</p>
        </div>
        <button type="button" class="user-settings-add" onclick="openUserSettingModal()">
            + Add {{ $taxonomy['label'] }}
        </button>
    </div>

    <form method="GET" action="{{ route('superadmin.user-settings.index') }}" class="user-settings-toolbar">
        <input type="hidden" name="type" value="{{ $type }}">
        <label for="userSettingSearch" class="user-settings-visually-hidden">Search {{ strtolower($taxonomy['plural']) }}</label>
        <input id="userSettingSearch" class="user-settings-search" type="search" name="search"
            value="{{ request('search') }}" maxlength="255"
            placeholder="Search {{ strtolower($taxonomy['plural']) }} by name">
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
                    <th>{{ $taxonomy['label'] }} name</th>
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
                        <td colspan="4" class="user-settings-empty">
                            No {{ strtolower($taxonomy['plural']) }} added yet.
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
                {{ $editing ? 'Edit ' . $taxonomy['label'] : 'Add ' . $taxonomy['label'] }}
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
                <label for="userSettingName">{{ $taxonomy['label'] }} name</label>
                <input id="userSettingName" type="text" name="name" maxlength="255" required
                    value="{{ old('name', $editing ? $editing->name : '') }}"
                    placeholder="Enter {{ strtolower($taxonomy['label']) }} name">
                @error('name')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="user-settings-field">
                <label for="userSettingStatus">Status</label>
                <select id="userSettingStatus" name="status" required>
                    <option value="active" @selected(old('status', $editing ? $editing->status : 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $editing ? $editing->status : 'active') === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <span class="user-settings-field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="user-settings-submit">
                {{ $editing ? 'Save changes' : 'Add ' . $taxonomy['label'] }}
            </button>
        </form>
    </div>
</div>
