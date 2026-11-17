@push('styles')
<style>
    /* Attractive Modal Styles */
    .bn-modal-overlay {
        display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(5px);
        align-items: center; justify-content: center; animation: bnFadeIn 0.2s ease-out;
    }
    @keyframes bnFadeIn { from { opacity: 0; } to { opacity: 1; } }

    .bn-modal-box {
        background: #ffffff; width: 90%; max-width: 450px; border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;
        transform: translateY(0); animation: bnSlideUp 0.3s ease-out;
    }
    @keyframes bnSlideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .bn-modal-header {
        padding: 20px 24px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white; display: flex; justify-content: space-between; align-items: center;
    }
    .bn-modal-header h3 { margin: 0; font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }
    .bn-close-btn {
        background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px;
        border-radius: 50%; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;
    }
    .bn-close-btn:hover { background: rgba(255,255,255,0.4); }

    .bn-modal-body { padding: 24px; }
    .bn-form-group { margin-bottom: 18px; }
    .bn-form-group label { display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
    .bn-form-control {
        width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px;
        font-size: 14px; color: #0f172a; transition: all 0.2s; box-sizing: border-box;
    }
    .bn-form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
    textarea.bn-form-control { resize: vertical; min-height: 80px; }

    .bn-img-preview-box {
        width: 100%; height: 120px; border: 2px dashed #e5e7eb; border-radius: 10px;
        background: #f9fafb; display: flex; align-items: center; justify-content: center;
        margin-top: 10px; overflow: hidden; transition: all 0.2s;
    }
    .bn-img-preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .bn-img-placeholder { text-align: center; color: #9ca3af; font-size: 13px; }

    .bn-modal-footer {
        padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0;
        display: flex; justify-content: flex-end; gap: 12px;
    }
    .bn-btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
    .bn-btn-cancel { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
    .bn-btn-cancel:hover { background: #f1f5f9; }
    .bn-btn-save { background: #6366f1; color: white; }
    .bn-btn-save:hover { background: #4f46e5; }
</style>
@endpush

<!-- Modal HTML -->
<div id="newsModal" class="bn-modal-overlay">
    <div class="bn-modal-box">
        <div class="bn-modal-header">
            <h3 id="modalTitle">Add Bullet News</h3>
            <button class="bn-close-btn" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="newsForm" action="{{ route('superadmin.news.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="formMethod" name="_method" value="POST">
            <input type="hidden" id="newsId" name="id" value="">

            <div class="bn-modal-body">
                <div class="bn-form-group">
                    <label>Title <span style="color: red;">*</span></label>
                    <input type="text" name="title" id="newsTitle" class="bn-form-control" required placeholder="Enter quick news title...">
                </div>

                <div class="bn-form-group">
                    <label>Short Description <span style="font-size: 11px; color: #94a3b8; font-weight: 400;">(Optional)</span></label>
                    <textarea name="description" id="newsDescription" class="bn-form-control" rows="3" placeholder="Brief details about the news..."></textarea>
                </div>

                <div class="bn-form-group">
                    <label>Image <span style="font-size: 11px; color: #94a3b8; font-weight: 400;">(Optional)</span></label>
                    <input type="file" name="image" id="newsImage" class="bn-form-control" accept="image/*">
                    
                    <div class="bn-img-preview-box">
                        <img id="imagePreview" src="" alt="Preview" style="display: none;">
                        <div id="imagePlaceholder" class="bn-img-placeholder">
                            <span style="font-size: 24px;">🖼️</span>
                            <div style="margin-top: 4px;">No image selected</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bn-modal-footer">
                <button type="button" class="bn-btn bn-btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="bn-btn bn-btn-save">Save News</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Modal Functions
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Add Bullet News';
        document.getElementById('newsForm').action = "{{ route('superadmin.news.store') }}";
        document.getElementById('formMethod').value = "POST";
        document.getElementById('newsId').value = "";
        document.getElementById('newsForm').reset();
        resetImagePreview();
        document.getElementById('newsModal').style.display = 'flex';
    }

    function openEditModal(id, title, description, imageUrl) {
        document.getElementById('modalTitle').innerText = 'Edit Bullet News';
        document.getElementById('newsForm').action = `/superadmin/news/${id}`;
        document.getElementById('formMethod').value = "PUT";
        document.getElementById('newsId').value = id;
        
        document.getElementById('newsTitle').value = title;
        document.getElementById('newsDescription').value = description;
        
        const imagePreview = document.getElementById('imagePreview');
        const imagePlaceholder = document.getElementById('imagePlaceholder');
        
        if (imageUrl && imageUrl !== '') {
            imagePreview.src = imageUrl;
            imagePreview.style.display = 'block';
            imagePlaceholder.style.display = 'none';
        } else {
            resetImagePreview();
        }
        document.getElementById('newsModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('newsModal').style.display = 'none';
    }

    function resetImagePreview() {
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('imagePreview').src = '';
        document.getElementById('imagePlaceholder').style.display = 'block';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('newsModal');
        if (event.target == modal) closeModal();
    }

    // Live Image Preview
    document.getElementById('newsImage').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('imagePreview').src = event.target.result;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('imagePlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            resetImagePreview();
        }
    });
</script>
@endpush