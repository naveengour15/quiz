@extends('superadmin.layouts.app')
@section('title', 'Privacy Policy & CMS Pages')

@section('content')
    @push('styles')
        <style>
            .pp-page {
                width: 100%;
            }

            .pp-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 16px;
                margin-bottom: 22px;
            }

            .pp-head h1 {
                margin: 0;
                color: var(--ink);
                font-family: var(--font-display);
                font-size: 24px;
                font-weight: 800;
                letter-spacing: -0.02em;
            }

            .pp-head p {
                margin: 5px 0 0;
                color: var(--ink-faint);
                font-size: 13px;
            }

            .pp-add-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: 0;
                border-radius: 10px;
                padding: 10px 18px;
                background: linear-gradient(135deg, var(--indigo), var(--indigo-deep));
                color: #fff;
                font-family: var(--font-body);
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
                transition: transform .15s ease, box-shadow .2s ease;
            }

            .pp-add-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
            }

            .pp-layout {
                display: flex;
                align-items: flex-start;
                gap: 22px;
            }

            /* --- Left Tabs (User Settings Reference) --- */
            .pp-tabs {
                width: 290px;
                min-width: 290px;
                padding: 10px;
                border: 1px solid var(--line);
                border-radius: 14px;
                background: var(--surface);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            }

            .pp-tabs-title {
                padding: 8px 12px 10px;
                font-family: var(--font-mono);
                font-size: 10.5px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--ink-faint);
                border-bottom: 1px solid var(--line);
                margin-bottom: 8px;
            }

            .pp-tab-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 6px;
                padding: 12px 14px;
                border-radius: 10px;
                color: var(--ink);
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                transition: background .2s ease, color .2s ease, transform .15s ease;
            }

            .pp-tab-item:last-child {
                margin-bottom: 0;
            }

            .pp-tab-item .tab-left {
                display: flex;
                align-items: center;
                gap: 10px;
                min-width: 0;
            }

            .pp-tab-item .tab-icon {
                font-size: 16px;
                flex-shrink: 0;
            }

            .pp-tab-item .tab-label {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: 13px;
            }

            .pp-tab-item:hover {
                background: var(--paper);
                color: var(--indigo);
            }

            .pp-tab-item.active {
                background: var(--indigo);
                color: #fff;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            }

            .pp-tab-badge {
                font-family: var(--font-mono);
                font-size: 10px;
                font-weight: 700;
                padding: 2px 7px;
                border-radius: 999px;
                flex-shrink: 0;
                text-transform: uppercase;
                letter-spacing: .03em;
            }

            .pp-tab-badge.published {
                background: var(--emerald-bg);
                color: #047857;
            }

            .pp-tab-badge.draft {
                background: var(--gold-bg);
                color: #b45309;
            }

            .pp-tab-item.active .pp-tab-badge {
                background: rgba(255, 255, 255, 0.25);
                color: #fff;
            }

            /* --- Right Content Area --- */
            .pp-content {
                flex: 1;
                min-width: 0;
            }

            .pp-card {
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 14px;
                background: var(--surface);
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            }

            .pp-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 14px;
                padding: 20px 24px;
                border-bottom: 1px solid var(--line);
                background: linear-gradient(180deg, #FFFFFF, var(--paper));
            }

            .pp-card-title-group {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .pp-card-head h2 {
                margin: 0;
                color: var(--ink);
                font-family: var(--font-display);
                font-size: 19px;
                font-weight: 700;
            }

            .pp-slug-tag {
                font-family: var(--font-mono);
                font-size: 11.5px;
                font-weight: 600;
                background: var(--paper);
                border: 1px solid var(--line);
                padding: 3px 9px;
                border-radius: 6px;
                color: var(--indigo);
            }

            .pp-status-pill {
                font-size: 11px;
                font-weight: 700;
                padding: 4px 10px;
                border-radius: 999px;
            }

            .pp-status-pill.published {
                background: var(--emerald-bg);
                color: #047857;
                border: 1px solid #a7f3d0;
            }

            .pp-status-pill.draft {
                background: var(--gold-bg);
                color: #b45309;
                border: 1px solid #fde68a;
            }

            .pp-head-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .pp-toggle-view-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: var(--surface);
                border: 1px solid var(--line);
                color: var(--ink-soft);
                padding: 8px 14px;
                border-radius: 8px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all .15s ease;
            }

            .pp-toggle-view-btn:hover {
                border-color: var(--indigo);
                color: var(--indigo);
            }

            .pp-delete-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: var(--coral-bg);
                border: 1px solid #fca5a5;
                color: #dc2626;
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
            }

            .pp-card-body {
                padding: 24px;
            }

            /* --- Form Grid --- */
            .pp-grid-2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 18px;
                margin-bottom: 18px;
            }

            .pp-field {
                margin-bottom: 18px;
            }

            .pp-field label {
                display: block;
                margin-bottom: 7px;
                color: var(--ink);
                font-size: 13px;
                font-weight: 600;
            }

            .pp-field label .req {
                color: var(--coral);
            }

            .pp-field label .hint {
                font-size: 11px;
                font-weight: 400;
                color: var(--ink-faint);
                margin-left: 6px;
            }

            .pp-field input,
            .pp-field select,
            .pp-field textarea {
                width: 100%;
                border: 1px solid var(--line);
                border-radius: 9px;
                padding: 10px 13px;
                background: var(--surface);
                color: var(--ink);
                font-family: var(--font-body);
                font-size: 13px;
                box-sizing: border-box;
                transition: border-color .15s ease, box-shadow .15s ease;
            }

            .pp-field input:focus,
            .pp-field select:focus,
            .pp-field textarea:focus {
                outline: none;
                border-color: var(--indigo);
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
            }

            /* --- Toolbar & Content Editor --- */
            .pp-editor-toolbar {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 4px;
                padding: 8px 10px;
                background: var(--paper);
                border: 1px solid var(--line);
                border-bottom: 0;
                border-radius: 9px 9px 0 0;
            }

            .pp-editor-btn {
                border: 1px solid var(--line);
                background: var(--surface);
                color: var(--ink);
                border-radius: 6px;
                padding: 5px 10px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all .15s ease;
            }

            .pp-editor-btn:hover {
                background: var(--indigo);
                border-color: var(--indigo);
                color: #fff;
            }

            .pp-editor-separator {
                width: 1px;
                height: 18px;
                background: var(--line);
                margin: 0 4px;
            }

            .pp-textarea {
                min-height: 320px;
                border-radius: 0 0 9px 9px !important;
                font-family: var(--font-mono) !important;
                font-size: 12.5px !important;
                line-height: 1.6;
                resize: vertical;
            }

            /* Live Preview Box */
            .pp-preview-box {
                display: none;
                min-height: 320px;
                padding: 22px;
                border: 1px solid var(--line);
                border-radius: 0 0 9px 9px;
                background: #fafafc;
                line-height: 1.7;
                color: #2e384d;
                overflow-y: auto;
                max-height: 520px;
            }

            .pp-preview-box.active {
                display: block;
            }

            .pp-preview-box h2 {
                font-family: var(--font-display);
                font-size: 20px;
                font-weight: 800;
                color: var(--ink);
                margin: 0 0 14px;
            }

            .pp-preview-box h3 {
                font-family: var(--font-display);
                font-size: 16px;
                font-weight: 700;
                color: var(--ink);
                margin: 18px 0 8px;
            }

            .pp-preview-box p {
                margin: 0 0 12px;
                font-size: 13.5px;
            }

            .pp-preview-box ul,
            .pp-preview-box ol {
                margin: 0 0 16px 24px;
                font-size: 13.5px;
            }

            .pp-preview-box li {
                margin-bottom: 6px;
            }

            .pp-preview-box a {
                color: var(--indigo);
                text-decoration: underline;
            }

            /* --- Form Footer --- */
            .pp-form-foot {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                padding-top: 18px;
                border-top: 1px solid var(--line);
                margin-top: 24px;
            }

            .pp-last-updated {
                font-size: 11.5px;
                color: var(--ink-faint);
            }

            .pp-submit-btn {
                border: 0;
                border-radius: 9px;
                padding: 11px 26px;
                background: linear-gradient(135deg, var(--indigo), var(--indigo-deep));
                color: #fff;
                font-family: var(--font-body);
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
                transition: all .2s ease;
            }

            .pp-submit-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
            }

            /* --- Modal --- */
            .pp-modal {
                position: fixed;
                z-index: 9999;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .pp-modal.active {
                display: flex;
            }

            .pp-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, .65);
                backdrop-filter: blur(4px);
            }

            .pp-modal-card {
                position: relative;
                width: 100%;
                max-width: 520px;
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 24px;
                background: var(--surface);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
                animation: ppPopIn .2s cubic-bezier(0.16, 1, 0.3, 1);
            }

            @keyframes ppPopIn {
                from {
                    opacity: 0;
                    transform: scale(0.96);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            .pp-modal-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 20px;
                padding-bottom: 14px;
                border-bottom: 1px solid var(--line);
            }

            .pp-modal-head h3 {
                margin: 0;
                color: var(--ink);
                font-family: var(--font-display);
                font-size: 18px;
                font-weight: 700;
            }

            .pp-close {
                border: 0;
                background: transparent;
                color: var(--ink-faint);
                font-size: 22px;
                cursor: pointer;
                line-height: 1;
            }

            .pp-close:hover {
                color: var(--ink);
            }

            /* Responsive */
            @media (max-width: 900px) {
                .pp-layout {
                    flex-direction: column;
                }

                .pp-tabs {
                    display: flex;
                    width: 100%;
                    min-width: 0;
                    overflow-x: auto;
                    gap: 6px;
                }

                .pp-tabs-title {
                    display: none;
                }

                .pp-tab-item {
                    flex: 0 0 auto;
                    margin-bottom: 0;
                    white-space: nowrap;
                }

                .pp-grid-2 {
                    grid-template-columns: 1fr;
                    gap: 0;
                }
            }
        </style>
    @endpush

    <div class="pp-page">
        {{-- Header --}}
        <div class="pp-head">
            <div>
                <h1>Privacy Policy & CMS Pages</h1>
                <p>Manage Privacy Policy, Terms & Conditions, Delete Account, and other policy pages.</p>
            </div>
            <button type="button" class="pp-add-btn" onclick="openPolicyModal()">
                <span>+</span> Add Policy Page
            </button>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div style="margin-bottom:16px; border-radius:9px; padding:12px 16px; background:var(--emerald-bg); color:#047857; font-weight:600; border:1px solid #a7f3d0;" role="status">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div style="margin-bottom:16px; border:1px solid #fecaca; border-radius:9px; padding:12px 16px; background:var(--coral-bg); color:#b91c1c; font-weight:600;" role="alert">
                ⚠ {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-bottom:16px; border:1px solid #fecaca; border-radius:9px; padding:12px 16px; background:var(--coral-bg); color:#b91c1c;" role="alert">
                <ul style="margin:0; padding-left:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Layout: Left Tabs + Right Content --}}
        <div class="pp-layout">
            {{-- Tabs --}}
            <nav class="pp-tabs" aria-label="Policy Pages">
                <div class="pp-tabs-title">Pages</div>
                @forelse ($pages as $p)
                    <a href="{{ route('superadmin.privacy-policy.index', ['slug' => $p->slug]) }}"
                        class="pp-tab-item {{ $activePage && $activePage->id === $p->id ? 'active' : '' }}"
                        @if ($activePage && $activePage->id === $p->id) aria-current="page" @endif>
                        <div class="tab-left">
                            <span class="tab-icon">{{ $icons[$p->slug] ?? '📄' }}</span>
                            <span class="tab-label" title="{{ $p->title }}">{{ $p->title }}</span>
                        </div>
                        <span class="pp-tab-badge {{ $p->status }}">{{ ucfirst($p->status) }}</span>
                    </a>
                @empty
                    <div style="padding:14px; text-align:center; color:var(--ink-faint); font-size:12px;">
                        No pages added yet.
                    </div>
                @endforelse
            </nav>

            {{-- Right Content --}}
            <section class="pp-content">
                @if ($activePage)
                    <div class="pp-card">
                        {{-- Card Head --}}
                        <div class="pp-card-head">
                            <div class="pp-card-title-group">
                                <h2>{{ $activePage->title }}</h2>
                                <span class="pp-slug-tag">/{{ $activePage->slug }}</span>
                                <span class="pp-status-pill {{ $activePage->status }}">
                                    ● {{ ucfirst($activePage->status) }}
                                </span>
                            </div>

                            <div class="pp-head-actions">
                                <button type="button" class="pp-toggle-view-btn" id="togglePreviewBtn" onclick="toggleEditorPreview()">
                                    <span id="previewBtnIcon">👁</span>
                                    <span id="previewBtnText">Preview Live</span>
                                </button>

                                @if (!in_array($activePage->slug, ['privacy-policy', 'terms-conditions', 'delete-account'], true))
                                    <form method="POST" action="{{ route('superadmin.privacy-policy.destroy', $activePage->id) }}"
                                        onsubmit="return confirm('Are you sure you want to delete this page?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="pp-delete-btn">
                                            🗑 Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="pp-card-body">
                            <form method="POST" action="{{ route('superadmin.privacy-policy.update', $activePage->id) }}">
                                @csrf
                                @method('PUT')

                                <div class="pp-grid-2">
                                    <div class="pp-field">
                                        <label for="pageTitle">Page Title <span class="req">*</span></label>
                                        <input id="pageTitle" type="text" name="title" maxlength="191" required
                                            value="{{ old('title', $activePage->title) }}"
                                            placeholder="Enter page title">
                                    </div>

                                    <div class="pp-field">
                                        <label for="pageSlug">URL Slug <span class="req">*</span> <span class="hint">— unique identifier</span></label>
                                        <input id="pageSlug" type="text" name="slug" maxlength="191" required
                                            value="{{ old('slug', $activePage->slug) }}"
                                            placeholder="e.g. privacy-policy">
                                    </div>
                                </div>

                                <div class="pp-grid-2">
                                    <div class="pp-field">
                                        <label for="pageStatus">Status <span class="req">*</span></label>
                                        <select id="pageStatus" name="status" required>
                                            <option value="published" @selected(old('status', $activePage->status) === 'published')>Published (Live)</option>
                                            <option value="draft" @selected(old('status', $activePage->status) === 'draft')>Draft (Hidden)</option>
                                        </select>
                                    </div>

                                    <div class="pp-field">
                                        <label for="pageMetaTitle">Meta Title <span class="hint">— For SEO search results</span></label>
                                        <input id="pageMetaTitle" type="text" name="meta_title" maxlength="191"
                                            value="{{ old('meta_title', $activePage->meta_title) }}"
                                            placeholder="SEO Meta Title (optional)">
                                    </div>
                                </div>

                                <div class="pp-field">
                                    <label for="pageMetaDesc">Meta Description <span class="hint">— Short summary for search engines</span></label>
                                    <input id="pageMetaDesc" type="text" name="meta_description" maxlength="255"
                                        value="{{ old('meta_description', $activePage->meta_description) }}"
                                        placeholder="SEO Meta Description (optional)">
                                </div>

                                {{-- Rich Toolbar & Content Textarea --}}
                                <div class="pp-field">
                                    <label>Page Content <span class="hint">— HTML or formatted text</span></label>

                                    <div class="pp-editor-toolbar">
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('bold')" title="Bold"><b>B</b></button>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('italic')" title="Italic"><i>I</i></button>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('underline')" title="Underline"><u>U</u></button>
                                        <div class="pp-editor-separator"></div>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('h2')" title="Heading 2">H2</button>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('h3')" title="Heading 3">H3</button>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('p')" title="Paragraph">&para;</button>
                                        <div class="pp-editor-separator"></div>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('ul')" title="Bullet List">• List</button>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('ol')" title="Numbered List">1. List</button>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('link')" title="Insert Link">🔗 Link</button>
                                        <button type="button" class="pp-editor-btn" onclick="formatContent('hr')" title="Horizontal Rule">&HorizontalLine;</button>
                                    </div>

                                    <textarea id="policyContentTextarea" class="pp-textarea" name="content"
                                        placeholder="Write page description and content here (HTML supported)...">{{ old('content', $activePage->content) }}</textarea>

                                    <div id="policyPreviewBox" class="pp-preview-box"></div>
                                </div>

                                {{-- Footer / Submit --}}
                                <div class="pp-form-foot">
                                    <div class="pp-last-updated">
                                        @if ($activePage->updated_at)
                                            Last modified: {{ $activePage->updated_at->format('d M Y, h:i A') }}
                                        @else
                                            Not modified yet
                                        @endif
                                    </div>

                                    <button type="submit" class="pp-submit-btn">
                                        💾 Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="pp-card" style="padding:40px; text-align:center;">
                        <h3>No page selected</h3>
                        <p style="color:var(--ink-faint); margin-top:6px;">Please choose a policy page from the left or create a new one.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>

    {{-- Modal to Add New Policy Page --}}
    <div id="policyAddModal" class="pp-modal {{ $errors->has('title') || $errors->has('slug') ? 'active' : '' }}">
        <div class="pp-backdrop" onclick="closePolicyModal()"></div>
        <div class="pp-modal-card" role="dialog" aria-modal="true" aria-labelledby="policyModalTitle">
            <div class="pp-modal-head">
                <h3 id="policyModalTitle">+ Add New Policy Page</h3>
                <button type="button" class="pp-close" aria-label="Close" onclick="closePolicyModal()">×</button>
            </div>

            <form method="POST" action="{{ route('superadmin.privacy-policy.store') }}">
                @csrf

                <div class="pp-field">
                    <label for="newPolicyTitle">Page Title <span class="req">*</span></label>
                    <input id="newPolicyTitle" type="text" name="title" maxlength="191" required
                        placeholder="e.g. Refund Policy, Disclaimer..." oninput="autoGenerateSlug(this.value)">
                </div>

                <div class="pp-field">
                    <label for="newPolicySlug">Slug <span class="req">*</span></label>
                    <input id="newPolicySlug" type="text" name="slug" maxlength="191" required
                        placeholder="e.g. refund-policy">
                </div>

                <div class="pp-field">
                    <label for="newPolicyStatus">Status <span class="req">*</span></label>
                    <select id="newPolicyStatus" name="status" required>
                        <option value="published" selected>Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>

                <div class="pp-field">
                    <label for="newPolicyMetaTitle">Meta Title (Optional)</label>
                    <input id="newPolicyMetaTitle" type="text" name="meta_title" maxlength="191"
                        placeholder="e.g. Refund Policy — FSR Quiz">
                </div>

                <div class="pp-field">
                    <label for="newPolicyContent">Content / Description (Optional)</label>
                    <textarea id="newPolicyContent" name="content" style="min-height:90px;"
                        placeholder="Write initial description (optional)..."></textarea>
                </div>

                <button type="submit" class="pp-submit-btn" style="width:100%;">
                    Create Policy Page
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openPolicyModal() {
                document.getElementById('policyAddModal').classList.add('active');
                document.getElementById('newPolicyTitle').focus();
            }

            function closePolicyModal() {
                document.getElementById('policyAddModal').classList.remove('active');
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePolicyModal();
                }
            });

            function autoGenerateSlug(text) {
                const slugInput = document.getElementById('newPolicySlug');
                if (slugInput) {
                    slugInput.value = text.toString().toLowerCase().trim()
                        .replace(/—/g, '-')
                        .replace(/&/g, '-and-')
                        .replace(/[\s\W-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }
            }

            function formatContent(type) {
                const textarea = document.getElementById('policyContentTextarea');
                if (!textarea) return;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const selected = textarea.value.substring(start, end);
                let replacement = '';

                switch (type) {
                    case 'bold':
                        replacement = `<strong>${selected || 'Bold text'}</strong>`;
                        break;
                    case 'italic':
                        replacement = `<em>${selected || 'Italic text'}</em>`;
                        break;
                    case 'underline':
                        replacement = `<u>${selected || 'Underlined text'}</u>`;
                        break;
                    case 'h2':
                        replacement = `\n<h2>${selected || 'Heading 2'}</h2>\n`;
                        break;
                    case 'h3':
                        replacement = `\n<h3>${selected || 'Heading 3'}</h3>\n`;
                        break;
                    case 'p':
                        replacement = `\n<p>${selected || 'Paragraph text...'}</p>\n`;
                        break;
                    case 'ul':
                        replacement = `\n<ul>\n    <li>${selected || 'List item 1'}</li>\n    <li>List item 2</li>\n</ul>\n`;
                        break;
                    case 'ol':
                        replacement = `\n<ol>\n    <li>${selected || 'Step 1'}</li>\n    <li>Step 2</li>\n</ol>\n`;
                        break;
                    case 'link':
                        const url = prompt('Enter URL:', 'https://');
                        if (url) {
                            replacement = `<a href="${url}" target="_blank">${selected || url}</a>`;
                        } else {
                            return;
                        }
                        break;
                    case 'hr':
                        replacement = `\n<hr>\n`;
                        break;
                }

                textarea.setRangeText(replacement, start, end, 'end');
                textarea.focus();
                updateLivePreview();
            }

            let isPreviewMode = false;
            function toggleEditorPreview() {
                const textarea = document.getElementById('policyContentTextarea');
                const previewBox = document.getElementById('policyPreviewBox');
                const btnIcon = document.getElementById('previewBtnIcon');
                const btnText = document.getElementById('previewBtnText');

                if (!isPreviewMode) {
                    updateLivePreview();
                    textarea.style.display = 'none';
                    previewBox.classList.add('active');
                    btnIcon.textContent = '✏️';
                    btnText.textContent = 'Edit Code';
                    isPreviewMode = true;
                } else {
                    textarea.style.display = 'block';
                    previewBox.classList.remove('active');
                    btnIcon.textContent = '👁';
                    btnText.textContent = 'Preview Live';
                    isPreviewMode = false;
                }
            }

            function updateLivePreview() {
                const textarea = document.getElementById('policyContentTextarea');
                const previewBox = document.getElementById('policyPreviewBox');
                if (previewBox && textarea) {
                    previewBox.innerHTML = textarea.value || '<p style="color:#8E92AC; font-style:italic;">No description or content added yet.</p>';
                }
            }
        </script>
    @endpush
@endsection
