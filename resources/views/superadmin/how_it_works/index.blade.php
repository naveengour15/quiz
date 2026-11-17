@extends('superadmin.layouts.app')
@section('title', 'How It Works')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #a7f3d0; font-weight: 500;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>How It Works</h1>
                <p>Manage and update the steps shown to users. Edit any field below and save all changes at once.</p>
            </div>
        </div>

        <div class="form-card">
            <div class="fh">
                <h3>Platform Steps</h3>
            </div>
            
            <form method="POST" action="{{ route('superadmin.how-it-works.update') }}">
                @csrf
                @method('PUT')
                
                <div class="setting-grid-form setting-grid-form-02">
                    @forelse($items as $index => $item)
                        <div style="grid-column: 1 / -1; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border, #e5e7eb);">
                            <span style="font-size: 12px; font-weight: 700; color: var(--primary, #3b82f6); text-transform: uppercase; letter-spacing: 0.5px;">
                                
                            </span>
                        </div>

                        <!-- Hidden ID for each item -->
                        <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                        <div class="rich-input-box">
                            <div class="field">
                                <label>Step Title</label>
                                <input type="text" 
                                    name="items[{{ $item->id }}][title]" 
                                    value="{{ old('items.'.$item->id.'.title', $item->title) }}" 
                                    required 
                                    placeholder="e.g., Register & Get Bonus">
                            </div>
                            
                            {{-- <div class="field" style="grid-column: 1 / -1;">
                                <label>Step Description</label>
                                <textarea name="items[{{ $item->id }}][description]" 
                                        rows="3" 
                                        required 
                                        placeholder="Explain this step clearly...">{{ old('items.'.$item->id.'.description', $item->description) }}</textarea>
                            </div> --}}

                            <div class="field">
                                 <label>Step Description</label>
                                 <div class="rich-text-editor">
                                <!-- Toolbar -->
                                <div class="toolbar">

                                    <button type="button" onclick="formatText('bold')">
                                        Bold
                                    </button>

                                    <button type="button" onclick="formatText('italic')">
                                        Italic
                                    </button>

                                    <button type="button" onclick="formatText('underline')">
                                        Underline
                                    </button>

                                    <button type="button" onclick="formatText('insertUnorderedList')">
                                        • List
                                    </button>

                                    <button type="button" onclick="formatText('insertOrderedList')">
                                        1. List
                                    </button>

                                </div>
                                <!-- Editor -->
                                <div
                                    id="editor"
                                    contenteditable="true"
                                    class="editor">
                                    Write your description here...
                                </div>
                                  </div>

                            </div>
                        </div>
                    @empty
                        <div style="grid-column: 1 / -1; text-align: center; padding: 30px; color: var(--ink-faint, #6b7280);">
                            No steps found in the database. Please add them via database seeder or direct DB insert first.
                        </div>
                    @endforelse
                </div>

                <div class="form-foot">
                    <button class="btn btn-primary" type="submit">
                        💾 Save All Changes
                    </button>
                </div>
            </form>
        </div>

        <p style="color:var(--ink-faint);font-size:12px; margin-top: 16px;">
            💡 Tip: Changes are saved in bulk. Make sure to fill out all required fields before clicking "Save All Changes".
       
    </div>

    <script>
    function formatText(command) {
        document.execCommand(command, false, null);
        document.getElementById("editor").focus();
    }
</script>
@endsection