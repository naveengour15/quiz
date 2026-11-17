@extends('superadmin.layouts.app')
@section('title', 'CMS')
@section('content')
    @push('styles')
        <style>
            .cms-status-change {
                min-width: 140px;
                padding: 8px 12px;
                border: 1px solid #dcdfe6;
                border-radius: 8px;
                background: #fff;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .cms-status-change:focus {
                outline: none;
                border-color: #4F46E5;
            }
        </style>
    @endpush
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>CMS Pages</h1>
                <p>Static content pages — About, Terms, Privacy, FAQ, and more.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td>{{ $page->title }}</td>
                            <td class="mono">/{{ $page->slug }}</td>
                            {{-- <td>@if ($page->status === 'published')<span class="badge success">Published</span>@else<span class="badge neutral">Draft</span>@endif</td> --}}

                            <td>
                                <select class="cms-status-change" data-id="{{ $page->id }}">
                                    <option value="draft" {{ $page->status == 'draft' ? 'selected' : '' }}>
                                        Draft
                                    </option>

                                    <option value="published" {{ $page->status == 'published' ? 'selected' : '' }}>
                                        Published
                                    </option>
                                </select>
                            </td>
                            <td class="mono">{{ $page->updated_at->format('d M Y') }}</td>
                            <td class="row-actions"><a class="icon-btn"
                                    href="{{ route('superadmin.cms.index', ['edit' => $page->id]) }}">✎</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;color:var(--ink-faint);">No pages yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Page' : 'Add Page' }}</h3>
            </div>
            <form method="POST"
                action="{{ $editing ? route('superadmin.cms.update', $editing) : route('superadmin.cms.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid">
                    <div class="field"><label>Page Title <span class="req">*</span></label><input type="text"
                            name="title" value="{{ $editing->title ?? old('title') }}" required></div>
                    <div class="field"><label>Slug <span class="req">*</span> <span class="hint">— e.g.
                                terms-of-service</span></label><input type="text" name="slug"
                            value="{{ $editing->slug ?? old('slug') }}" required></div>
                    <div class="field full"><label>Content <span class="req">*</span> <span class="hint">— HTML
                                allowed</span></label>
                        <textarea name="content" style="min-height:180px;" required>{{ $editing->content ?? old('content') }}</textarea>
                    </div>
                    <div class="field"><label>Meta Title</label><input type="text" name="meta_title"
                            value="{{ $editing->meta_title ?? old('meta_title') }}"></div>
                    <div class="field"><label>Meta Description</label><input type="text" name="meta_description"
                            value="{{ $editing->meta_description ?? old('meta_description') }}"></div>
                    <div class="field"><label>Status <span class="req">*</span></label>
                        <select name="status" required>
                            <option value="draft" @selected(($editing->status ?? 'draft') === 'draft')>Draft</option>
                            <option value="published" @selected(($editing->status ?? '') === 'published')>Published</option>
                        </select>
                    </div>
                </div>
                <div class="form-foot">
                    @if ($editing)
                        <a class="btn btn-ghost" href="{{ route('superadmin.cms.index') }}">Cancel</a>
                    @endif
                    <button class="btn btn-primary" type="submit">Save Page</button>
                </div>
            </form>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        document.addEventListener('change', function(e) {

            if (!e.target.classList.contains('cms-status-change')) {
                return;
            }

            fetch("{{ route('superadmin.cms.changeStatus') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: e.target.dataset.id,
                        status: e.target.value
                    })
                })
                .then(res => res.json())
                .then(data => {

                    if (data.success) {

                        showAlert(
                            'success',
                            'Success',
                            'CMS  status updated successfully.'
                        );

                    } else {

                        showAlert(
                            'warning',
                            'Warning',
                            'Unable to update status.'
                        );

                    }

                })
                .catch(() => {

                    showAlert(
                        'error',
                        'Error',
                        'Something went wrong.'
                    );

                });

        });
    </script>
@endpush
