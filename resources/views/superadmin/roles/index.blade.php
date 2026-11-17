@extends('superadmin.layouts.app')
@section('title', 'Roles & Permissions')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Roles & Permissions</h1>
                <p>Module-by-module access control for every internal role.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('superadmin.roles.permissions.update') }}">
            @csrf
            @method('PUT')
            <div class="matrix-wrap">
                <table class="matrix">
                    <thead>
                        <tr>
                            <th>Module</th>
                            @foreach ($roles as $role)
                                <th>{{ $role->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($modules as $module)
                            <tr>
                                <td>{{ $module }}</td>
                                @foreach ($roles as $role)
                                    <td>
                                        <input type="checkbox" name="permissions[{{ $role->id }}][]"
                                            value="{{ $module }}" @checked(in_array($module, $role->permissions ?? []))>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="color:var(--ink-faint);">No roles yet — add one below.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($roles->count())
                <div class="form-foot"><button class="btn btn-primary" type="submit">Save Permission Matrix</button></div>
            @endif
        </form>

        <div class="form-card" style="margin-top:20px;">
            <div class="fh">
                <h3>Add Role</h3>
            </div>
            <form method="POST" action="{{ route('superadmin.roles.store') }}">
                @csrf
                <div class="form-grid form-grid-02">
                    <div class="field"><label>Role Name <span class="req">*</span></label><input type="text"
                            name="name" placeholder="e.g. Regional Content Editor" required></div>
                    <div class="field"><label>Base Template <span class="hint">(optional)</span></label>
                        <select name="base_template">
                            <option value="">Start Blank</option>
                            @foreach ($roles as $r)
                                <option value="{{ $r->name }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full"><label>Description <span class="req">*</span></label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="field"><label>Requires 2FA <span class="req">*</span></label>
                        <select name="requires_2fa" required>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div class="form-foot"><button class="btn btn-primary" type="submit">Create Role</button></div>
            </form>
        </div>
    </div>

@endsection
