@extends('superadmin.layouts.app')

@section('title', 'Blog Management')

@section('content')
@push('styles')
<style>
    .blog-page { max-width: 1200px; margin: 0 auto; }
    .blog-head, .blog-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
    .blog-head { margin-bottom: 20px; }
    .blog-head h1 { font-size: 24px; }
    .blog-head p { color: var(--ink-soft); margin-top: 5px; }
    .blog-button {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        border: 0; border-radius: 8px; padding: 10px 15px; background: var(--indigo);
        color: #fff; font-weight: 600; text-decoration: none; cursor: pointer;
    }
    .blog-button:hover { background: var(--indigo-deep); color: #fff; }
    .blog-button.secondary { background: #fff; color: var(--ink-soft); border: 1px solid var(--line); }
    .blog-button.danger { background: #dc2626; }
    .blog-card { background: #fff; border: 1px solid var(--line); border-radius: 12px; margin-bottom: 18px; }
    .blog-toolbar { padding: 16px; }
    .blog-toolbar form { display: flex; gap: 10px; width: 100%; }
    .blog-input { min-width: 0; width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 7px; }
    .blog-table-wrap { overflow-x: auto; }
    .blog-table { width: 100%; border-collapse: collapse; }
    .blog-table th, .blog-table td { padding: 13px 16px; border-bottom: 1px solid #f0eef6; text-align: left; vertical-align: middle; }
    .blog-table th { color: var(--ink-soft); background: #faf9fd; font-size: 11px; text-transform: uppercase; }
    .blog-image { width: 68px; height: 48px; object-fit: cover; border: 1px solid var(--line); border-radius: 6px; background: #f8f7fb; }
    .blog-actions { display: flex; justify-content: flex-end; gap: 8px; }
    .blog-empty { padding: 35px; text-align: center; color: var(--ink-faint); }
    .blog-alert { padding: 12px 15px; margin-bottom: 16px; border-radius: 8px; }
    .blog-alert.success { color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; }
    .blog-alert.error { color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; }
    #blogModal {
        position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center;
        justify-content: center; padding: 20px; visibility: hidden; opacity: 0;
        transition: visibility .25s ease, opacity .25s ease;
    }
    #blogModal.active { visibility: visible; opacity: 1; }
    .blog-modal-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.65); backdrop-filter: blur(4px); }
    .blog-modal-content {
        position: relative; z-index: 1; width: 100%; max-width: 760px; height: 90vh; max-height: calc(100vh - 40px);
        display: flex; flex-direction: column; overflow: hidden; border-radius: 16px;
        background: #fff; box-shadow: 0 25px 50px -12px rgba(0,0,0,.25);
        transform: translateY(15px) scale(.98); transition: transform .25s ease;
    }
    #blogModal.active .blog-modal-content { transform: translateY(0) scale(1); }
    #blogForm { display:flex; flex:1; flex-direction:column; min-height:0; overflow:hidden; }
    .blog-modal-header, .blog-modal-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 22px; background:#fbfaff; }
    .blog-modal-header { flex:0 0 auto; border-bottom:1px solid var(--line); }
    .blog-modal-header h2 { margin:0; font-size:18px; }
    .blog-modal-body { flex:1 1 auto; min-height:0; padding:22px; overflow-y:auto; overscroll-behavior:contain; }
    .blog-modal-footer { flex:0 0 auto; justify-content:flex-end; border-top:1px solid var(--line); }
    .blog-modal-close { border:0; background:transparent; color:var(--ink-soft); font-size:22px; cursor:pointer; }
    .blog-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .blog-field.full { grid-column:1 / -1; }
    .blog-field label { display:block; margin-bottom:7px; font-weight:600; }
    .blog-field input, .blog-field textarea { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:7px; font:inherit; }
    .blog-field textarea { min-height:180px; resize:vertical; }
    .blog-field-error { display:block; margin-top:5px; color:#b91c1c; }
    .blog-help { display:block; margin-top:5px; color:var(--ink-faint); font-size:12px; }
    .blog-preview { display:block; max-width:240px; max-height:140px; margin-top:10px; object-fit:contain; border:1px solid var(--line); border-radius:7px; }
    .blog-preview[hidden] { display:none; }
    @media (max-width: 600px) {
        .blog-head { align-items: flex-start; flex-direction: column; }
        .blog-toolbar form { flex-wrap: wrap; }
        .blog-actions { justify-content: flex-start; }
        .blog-form-grid { grid-template-columns:1fr; }
        .blog-field.full { grid-column:auto; }
        #blogModal { padding:12px; }
        .blog-modal-content { height:calc(100vh - 24px); max-height:calc(100vh - 24px); }
        .blog-modal-body { padding:16px; }
    }
    .ck-editor__editable_inline {
    min-height: 220px;
    max-height: 400px;
    overflow-y: auto;
}

.ck.ck-editor {
    width: 100%;
}
</style>
@endpush

<div class="blog-page">
    <div class="blog-head">
        <div>
            <h1>Blog Management</h1>
            <p>manage blog posts.</p>
        </div>
        <button class="blog-button" type="button" onclick="openAddBlogModal()">+ Add Blog</button>
    </div>

    @if (session('success'))
        <div class="blog-alert success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="blog-alert error" role="alert">{{ session('error') }}</div>
    @endif

    <div class="blog-card blog-toolbar">
        <form method="GET" action="{{ route('superadmin.blogs.index') }}">
            <input class="blog-input" type="search" name="search" value="{{ request('search') }}"
                placeholder="Search title or slug" aria-label="Search blogs">
            <button class="blog-button" type="submit">Search</button>
            @if (request()->filled('search'))
                <a class="blog-button secondary" href="{{ route('superadmin.blogs.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="blog-card blog-table-wrap">
        <table class="blog-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Created</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($blogs as $blog)
                    <tr>
                        <td>
                            @if ($blog->image)
                                <img class="blog-image" src="{{ Storage::disk('s3')->url($blog->image) }}" alt="{{ $blog->title }}">
                            @else
                                <span aria-label="No image">—</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $blog->title }}</strong>
                           
                          @if ($blog->description)
                                <div style="margin-top:4px;color:var(--ink-soft)">
                                    {{ \Illuminate\Support\Str::limit(
                                        trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($blog->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
                                        90
                                    ) }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $blog->slug }}</td>
                        <td>{{ $blog->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="blog-actions">
                                <button  class="icon-btn blog-edit-button" type="button"
                                    data-id="{{ $blog->id }}" data-title="{{ $blog->title }}"
                                    data-description="{{ $blog->description }}"
                                    data-image="{{ $blog->image ? Storage::disk('s3')->url($blog->image) : '' }}">✎ </button>

                                      
                                <form method="POST" action="{{ route('superadmin.blogs.destroy', $blog) }}"
                                    onsubmit="return confirm('Delete this blog? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                               
                                     <button type="submit" class="icon-btn danger" title="Delete Banner" > 🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="blog-empty" colspan="5">No blogs found. Add your first blog to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $blogs->links() }}
</div>

@php
    $editingBlog = old('blog_id') ? \App\Models\Blog::find(old('blog_id')) : null;
@endphp

<div id="blogModal" class="{{ $errors->any() ? 'active' : '' }}" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
    <div class="blog-modal-backdrop" onclick="closeBlogModal()"></div>
    <div class="blog-modal-content" role="dialog" aria-modal="true" aria-labelledby="blogModalTitle">
        <div class="blog-modal-header">
            <h2 id="blogModalTitle">Add Blog</h2>
            <button type="button" class="blog-modal-close" aria-label="Close" onclick="closeBlogModal()">×</button>
        </div>

        <form id="blogForm" method="POST" action="{{ route('superadmin.blogs.store') }}" enctype="multipart/form-data">
            @csrf
            <input id="blogMethod" type="hidden" name="_method" value="POST">
            <input id="blogId" type="hidden" name="blog_id" value="{{ old('blog_id') }}">
            <div class="blog-modal-body">
                <div class="blog-form-grid">
                    <div class="blog-field">
                        <label for="blogTitle">Title <span aria-hidden="true">*</span></label>
                        <input id="blogTitle" type="text" name="title" value="{{ old('title') }}" maxlength="255" required>
                        @error('title') <span class="blog-field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="blog-field">
                        <label for="blogSlug"> slug</label>
                        <input id="blogSlug" type="text" value="" readonly aria-describedby="blogSlugHelp">
                       
                    </div>
                    {{-- <div class="blog-field full">
                        <label for="blogDescription">Description</label>
                        <textarea id="blogDescription" name="description">{{ old('description') }}</textarea>
                        @error('description') <span class="blog-field-error">{{ $message }}</span> @enderror
                    </div> --}}
                    <div class="blog-field full">
                        <label for="blogDescription">Description</label>

                        <textarea id="blogDescription" name="description">{{ old('description') }}</textarea>

                        @error('description')
                            <span class="blog-field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="blog-field full">
                        <label for="blogImage"> image</label>
                        <input id="blogImage" type="file" name="image"
                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif">
                        <small class="blog-help"> JPG, PNG, WEBP, or GIF; maximum size 10 MB.</small>
                        <img id="blogImagePreview" class="blog-preview" alt="Blog image preview" hidden>
                        @error('image') <span class="blog-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="blog-modal-footer">
                <button class="blog-button secondary" type="button" onclick="closeBlogModal()">Cancel</button>
                <button id="blogSubmitButton" class="blog-button" type="submit">Create Blog</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
@push('scripts')

<script>
    const blogModal = document.getElementById('blogModal');
    const blogForm = document.getElementById('blogForm');
    const blogMethod = document.getElementById('blogMethod');
    const blogImage = document.getElementById('blogImage');
    const blogImagePreview = document.getElementById('blogImagePreview');
    const blogIndexUrl = @js(route('superadmin.blogs.index'));
    const blogStoreUrl = @js(route('superadmin.blogs.store'));
    const existingBlogSlugs = @js($blogs->getCollection()->mapWithKeys(fn ($blog) => [$blog->id => $blog->slug]));

    // CKEditor variables
    let blogEditor = null;
    let blogSubmitting = false;

    // Initialize CKEditor
    const blogEditorReady = ClassicEditor
        .create(document.querySelector('#blogDescription'), {
            toolbar: [
                'heading',
                '|',
                'bold',
                'italic',
                'underline',
                'link',
                '|',
                'bulletedList',
                'numberedList',
                '|',
                'outdent',
                'indent',
                '|',
                'insertTable',
                'blockQuote',
                '|',
                'undo',
                'redo'
            ]
        })
        .then(editor => {
            blogEditor = editor;
            return editor;
        })
        .catch(error => {
            console.error('CKEditor initialization failed:', error);
            return null;
        });

    function updateBlogSlug(excludeId = null) {
        const title = document.getElementById('blogTitle').value;

        if (!title.trim()) {
            document.getElementById('blogSlug').value = '';
            return;
        }

        const baseSlug = title.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'blog';

        const existingSlugs = Object.entries(existingBlogSlugs)
            .filter(([id]) => id !== String(excludeId))
            .map(([, slug]) => slug);

        let slug = baseSlug;
        let suffix = 2;

        while (existingSlugs.includes(slug)) {
            slug = `${baseSlug}-${suffix}`;
            suffix++;
        }

        document.getElementById('blogSlug').value = slug;
    }

    function openAddBlogModal(resetForm = true) {
        if (resetForm) {
            blogForm.reset();

            blogEditorReady.then(editor => {
                if (editor) {
                    editor.setData('');
                }
            });

            blogForm.action = blogStoreUrl;
            blogMethod.value = 'POST';

            document.getElementById('blogId').value = '';
            document.getElementById('blogModalTitle').textContent = 'Add Blog';
            document.getElementById('blogSubmitButton').textContent = 'Create Blog';

            blogImagePreview.src = '';
            blogImagePreview.hidden = true;
        }

        updateBlogSlug();

        blogModal.classList.add('active');
        blogModal.setAttribute('aria-hidden', 'false');
    }

    function openEditBlogModal(id, title, description, imageUrl) {
        blogForm.reset();

        blogForm.action = blogIndexUrl + '/' + encodeURIComponent(id);
        blogMethod.value = 'PUT';

        document.getElementById('blogId').value = id;
        document.getElementById('blogTitle').value = title || '';

        // Set existing description in CKEditor
        blogEditorReady.then(editor => {
            if (editor) {
                editor.setData(description || '');
            }
        });

        document.getElementById('blogModalTitle').textContent = 'Edit Blog';
        document.getElementById('blogSubmitButton').textContent = 'Update Blog';

        blogImagePreview.src = imageUrl || '';
        blogImagePreview.hidden = !imageUrl;

        updateBlogSlug(id);

        blogModal.classList.add('active');
        blogModal.setAttribute('aria-hidden', 'false');
    }

    function closeBlogModal() {
        blogModal.classList.remove('active');
        blogModal.setAttribute('aria-hidden', 'true');
    }

    // Edit button click
    document.querySelectorAll('.blog-edit-button').forEach(button => {
        button.addEventListener('click', () => openEditBlogModal(
            button.dataset.id,
            button.dataset.title,
            button.dataset.description,
            button.dataset.image
        ));
    });

    // Auto-generate slug when title changes
    document.getElementById('blogTitle').addEventListener('input', () => {
        updateBlogSlug(document.getElementById('blogId').value || null);
    });

    // Image preview
    blogImage.addEventListener('change', () => {
        const file = blogImage.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = event => {
            blogImagePreview.src = event.target.result;
            blogImagePreview.hidden = false;
        };

        reader.readAsDataURL(file);
    });

    // Close modal on Escape key
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeBlogModal();
        }
    });

    // Submit form with CKEditor data
    blogForm.addEventListener('submit', async function (event) {
        if (blogSubmitting) {
            return;
        }

        event.preventDefault();

        try {
            const editor = await blogEditorReady;

            if (editor) {
                editor.updateSourceElement();
            }

            blogSubmitting = true;

            blogForm.submit();

        } catch (error) {
            blogSubmitting = false;
            console.error('Blog form submission failed:', error);
        }
    });

    // Reopen modal if validation errors occur
    @if ($errors->any())
        @if ($editingBlog)
            openEditBlogModal(
                @js($editingBlog->id),
                @js(old('title', $editingBlog->title)),
                @js(old('description', $editingBlog->description)),
                @js($editingBlog->image ? Storage::disk('s3')->url($editingBlog->image) : '')
            );
        @else
            openAddBlogModal(false);
        @endif
    @endif
</script>

@endpush
@endsection