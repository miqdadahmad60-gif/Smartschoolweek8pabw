@extends('layout')
@section('content')
<main class="portal-content">
    <div class="page-heading">
        <div>
            <span class="page-eyebrow">Sarana Sekolah</span>
            <h1>{{ $user['role'] === 'guru' ? 'Pengajuan Fasilitas Siswa' : 'Fasilitas Sekolah' }}</h1>
        </div>
        <span class="score-pill" id="request-count">0 pengajuan</span>
    </div>

    <div id="ajax-flash" class="flash" style="display:none"></div>

    @if($user['role'] === 'siswa')
    <section class="management-form" id="borrow-form">
        <div>
            <h2 id="form-title">Ajukan peminjaman</h2>
            <p style="color:#5a6b70;margin:6px 0 0">Data dikirim lewat AJAX tanpa memuat ulang halaman.</p>
        </div>
        <form class="styled-form" id="facility-form" enctype="multipart/form-data" novalidate>
            <input type="hidden" id="request-id" value="">
            <div class="form-grid">
                <label>Fasilitas
                    <select name="facility_id">
                        <option value="">Pilih fasilitas</option>
                        @foreach($facilities as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" data-error-for="facility_id"></small>
                </label>
                <label>Tanggal digunakan
                    <input type="date" name="date_needed" min="{{ date('Y-m-d') }}">
                    <small class="field-error" data-error-for="date_needed"></small>
                </label>
            </div>
            <div class="form-grid">
                <label>Tanggal dikembalikan (opsional)
                    <input type="date" name="return_date" min="{{ date('Y-m-d') }}">
                    <small class="field-error" data-error-for="return_date"></small>
                </label>
                <label>Surat / bukti pengajuan (JPG, PNG, PDF - maks. 2 MB)
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="field-error" data-error-for="attachment"></small>
                    <small id="current-file"></small>
                </label>
            </div>
            <label>Tujuan
                <textarea name="purpose" rows="3"></textarea>
                <small class="field-error" data-error-for="purpose"></small>
            </label>
            <div>
                <button class="btn btn-orange" type="submit" id="btn-save">Kirim Pengajuan</button>
                <button class="btn btn-outline" type="button" id="btn-cancel" style="display:none">Batal ubah</button>
            </div>
        </form>
    </section>
    @endif

    <section class="content-section">
        <h2>Daftar pengajuan</h2>
        <div class="facility-requests" id="request-list"><p>Memuat data...</p></div>
    </section>
</main>

<style>
    .field-error{display:block;color:#c0392b;font-size:12.5px;margin-top:4px;min-height:0}
    .is-invalid{border-color:#c0392b!important}
    .request-card .status-actions button,.request-card .status-actions a{cursor:pointer}
</style>

<script>
(function () {
    'use strict';
    var ROLE = @json($user['role']);
    var CSRF = @json(csrf_token());
    var BASE = @json(url('/facilities'));
    var HEADERS = {'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'};

    var listEl = document.getElementById('request-list');
    var countEl = document.getElementById('request-count');
    var flashEl = document.getElementById('ajax-flash');
    var form = document.getElementById('facility-form');
    var rows = [];

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    function flash(msg, type) {
        flashEl.className = 'flash flash-' + (type || 'success');
        flashEl.textContent = msg;
        flashEl.style.display = 'block';
        clearTimeout(flash.t);
        flash.t = setTimeout(function () { flashEl.style.display = 'none'; }, 4000);
    }

    function render() {
        countEl.textContent = rows.length + ' pengajuan';
        if (!rows.length) { listEl.innerHTML = '<p>Belum ada pengajuan fasilitas.</p>'; return; }

        listEl.innerHTML = rows.map(function (r) {
            var actions = '';
            if (r.can_review) {
                actions += '<div class="status-actions">';
                [['disetujui', 'Setujui'], ['ditolak', 'Tolak'], ['dipinjam', 'Tandai Dipinjam'], ['dikembalikan', 'Dikembalikan']].forEach(function (s) {
                    actions += '<button type="button" data-status="' + s[0] + '" data-id="' + r.id + '">' + s[1] + '</button>';
                });
                actions += '<button type="button" data-del="' + r.id + '">Hapus</button></div>';
            } else if (r.can_edit) {
                actions += '<div class="status-actions"><a data-edit="' + r.id + '">Edit</a>' +
                           '<button type="button" data-del="' + r.id + '">Batalkan</button></div>';
            }
            var file = r.attachment_url
                ? '<small>Lampiran: <a href="' + esc(r.attachment_url) + '" target="_blank" rel="noopener">' + esc(r.attachment_name || 'Buka file') + '</a></small>'
                : '<small>Tanpa lampiran</small>';
            var note = r.teacher_note ? '<small>Catatan guru: ' + esc(r.teacher_note) + '</small>' : '';
            var back = r.return_date ? ' &middot; kembali ' + esc(r.return_date) : '';
            return '<article class="request-card"><div class="request-info">' +
                   '<span>' + esc(r.student_code) + ' &middot; ' + esc(r.created_at) + '</span>' +
                   '<h3>' + esc(r.student_name) + ' &mdash; ' + esc(r.facility) + '</h3>' +
                   '<p>' + esc(r.purpose) + '</p>' +
                   '<small>Tanggal penggunaan: ' + esc(r.date_needed) + back + '</small><br>' + file + '<br>' + note +
                   '</div><div class="request-status"><em class="status-label">' + esc(cap(r.status)) + '</em>' + actions + '</div></article>';
        }).join('');
    }

    function load() {
        return fetch(BASE + '/list', {headers: HEADERS})
            .then(function (res) { return res.json(); })
            .then(function (json) { rows = json.data; render(); });
    }

    // ---------- form siswa (tambah / ubah) ----------
    function clearErrors() {
        if (!form) return;
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
    }
    function showErrors(errors) {
        Object.keys(errors).forEach(function (field) {
            var input = form.querySelector('[name="' + field + '"]');
            var out = form.querySelector('[data-error-for="' + field + '"]');
            if (input) input.classList.add('is-invalid');
            if (out) out.textContent = errors[field][0];
        });
    }
    function resetForm() {
        form.reset();
        clearErrors();
        document.getElementById('request-id').value = '';
        document.getElementById('form-title').textContent = 'Ajukan peminjaman';
        document.getElementById('btn-save').textContent = 'Kirim Pengajuan';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('current-file').innerHTML = '';
    }
    function startEdit(id) {
        var r = rows.filter(function (x) { return String(x.id) === String(id); })[0];
        if (!r || !form) return;
        clearErrors();
        form.reset();
        document.getElementById('request-id').value = r.id;
        form.querySelector('[name="facility_id"]').value = r.facility_id;
        form.querySelector('[name="date_needed"]').value = r.date_needed;
        form.querySelector('[name="return_date"]').value = r.return_date || '';
        form.querySelector('[name="purpose"]').value = r.purpose;
        document.getElementById('form-title').textContent = 'Ubah pengajuan';
        document.getElementById('btn-save').textContent = 'Simpan Perubahan';
        document.getElementById('btn-cancel').style.display = 'inline-block';
        document.getElementById('current-file').innerHTML = r.attachment_url
            ? 'File saat ini: <a href="' + esc(r.attachment_url) + '" target="_blank" rel="noopener">' + esc(r.attachment_name || 'buka file') + '</a> (kosongkan jika tidak diganti)'
            : '';
        document.getElementById('borrow-form').scrollIntoView({behavior: 'smooth'});
    }

    if (form) {
        document.getElementById('btn-cancel').addEventListener('click', resetForm);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors();
            var id = document.getElementById('request-id').value;
            var fd = new FormData(form);
            if (id) fd.append('_method', 'PUT'); // spoofing: multipart + file tetap terkirim
            var btn = document.getElementById('btn-save');
            btn.disabled = true;

            fetch(id ? BASE + '/' + id : BASE, {method: 'POST', headers: HEADERS, body: fd})
                .then(function (res) { return res.json().then(function (json) { return {status: res.status, ok: res.ok, json: json}; }); })
                .then(function (r) {
                    if (r.status === 422) { showErrors(r.json.errors || {}); return; }
                    if (!r.ok) { flash(r.json.message || 'Terjadi kesalahan.', 'error'); return; }
                    flash(r.json.message);
                    resetForm();
                    return load();
                })
                .catch(function () { flash('Gagal terhubung ke server.', 'error'); })
                .then(function () { btn.disabled = false; });
        });
    }

    // ---------- aksi di daftar (edit / hapus / ubah status) ----------
    listEl.addEventListener('click', function (e) {
        var t = e.target;
        if (t.dataset.edit) { e.preventDefault(); startEdit(t.dataset.edit); return; }

        if (t.dataset.del) {
            if (!confirm(ROLE === 'guru' ? 'Hapus pengajuan ini?' : 'Batalkan pengajuan ini?')) return;
            fetch(BASE + '/' + t.dataset.del, {method: 'DELETE', headers: HEADERS})
                .then(function (res) { return res.json().then(function (j) { return {ok: res.ok, json: j}; }); })
                .then(function (r) { flash(r.json.message, r.ok ? 'success' : 'error'); return load(); });
            return;
        }

        if (t.dataset.status) {
            var fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('status', t.dataset.status);
            fetch(BASE + '/' + t.dataset.id + '/status', {method: 'POST', headers: HEADERS, body: fd})
                .then(function (res) { return res.json().then(function (j) { return {ok: res.ok, json: j}; }); })
                .then(function (r) { flash(r.json.message, r.ok ? 'success' : 'error'); return load(); });
        }
    });

    load();
})();
</script>
@endsection
