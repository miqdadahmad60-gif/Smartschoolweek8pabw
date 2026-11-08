@if($errors->any())
    <div class="flash flash-error">
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<label>Judul Materi
    <input name="title" value="{{ old('title', $material->title ?? '') }}" maxlength="180" required>
</label>
<label>Mata Pelajaran
    <input name="subject" value="{{ old('subject', $material->subject ?? ($user['subject'] ?? '')) }}" maxlength="100" required>
</label>
<label>Deskripsi
    <textarea name="description" rows="4">{{ old('description', $material->description ?? '') }}</textarea>
</label>
@if(isset($material) && $material->file_name)
    <p>File saat ini: <strong>{{ $material->original_name }}</strong></p>
@endif
<label>{{ isset($material) ? 'Ganti File (opsional)' : 'Pilih File Materi' }}
    <input type="file" name="material_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip" {{ isset($material) ? '' : 'required' }}>
    <small>Format: JPG, JPEG, PNG, PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP. Maksimal 10 MB.</small>
</label>
