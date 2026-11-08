<?php

namespace Modules\Facility\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Facility\Http\Requests\FacilityRequestForm;

/**
 * Modul Facility - CRUD AJAX untuk pengajuan peminjaman fasilitas sekolah.
 * Halaman hanya "kerangka"; seluruh data dibaca/ditulis lewat Fetch/XHR (JSON).
 *
 *  siswa : tambah, ubah, batalkan pengajuannya sendiri (selama status "menunggu")
 *  guru  : melihat semua pengajuan, mengubah status, menghapus
 *  ortu  : tidak punya akses (ditolak middleware role -> 403)
 */
class FacilityController extends Controller
{
    private const STATUSES = ['menunggu', 'disetujui', 'ditolak', 'dipinjam', 'dikembalikan'];

    private function profile(): array
    {
        return Auth::user()->profile();
    }

    /** Halaman kerangka (data dimuat via AJAX). */
    public function index()
    {
        return view('facility::index', [
            'user' => $this->profile(),
            'active' => 'facilities',
            'page_title' => 'Fasilitas Sekolah',
            'facilities' => DB::table('facilities')->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** JSON daftar pengajuan: siswa hanya miliknya, guru semuanya. */
    public function list(): JsonResponse
    {
        $user = $this->profile();

        $query = $this->baseQuery()->latest('facility_requests.created_at')->latest('facility_requests.id');
        if ($user['role'] === 'siswa') {
            $query->where('facility_requests.student_id', $user['id']);
        }

        $rows = $query->get()->map(fn ($row) => $this->present($row, $user));

        return response()->json(['data' => $rows]);
    }

    public function store(FacilityRequestForm $request): JsonResponse
    {
        $user = $this->profile();
        $values = [
            'student_id' => $user['id'],
            'facility_id' => $request->input('facility_id'),
            'purpose' => $request->input('purpose'),
            'date_needed' => $request->input('date_needed'),
            'return_date' => $request->input('return_date') ?: null,
            'status' => 'menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $values['attachment_path'] = $file->store('facility-attachments', 'public');
            $values['attachment_name'] = $file->getClientOriginalName();
        }

        $id = DB::table('facility_requests')->insertGetId($values);

        return response()->json([
            'message' => 'Pengajuan fasilitas berhasil dikirim kepada guru.',
            'data' => $this->present($this->baseQuery()->where('facility_requests.id', $id)->first(), $user),
        ], 201);
    }

    public function update(FacilityRequestForm $request, int $facilityRequest): JsonResponse
    {
        $user = $this->profile();
        $item = DB::table('facility_requests')
            ->where('id', $facilityRequest)
            ->where('student_id', $user['id'])
            ->first();

        abort_unless($item, 404, 'Pengajuan tidak ditemukan.');
        abort_unless($item->status === 'menunggu', 403, 'Pengajuan sudah diproses guru dan tidak dapat diubah.');

        $values = [
            'facility_id' => $request->input('facility_id'),
            'purpose' => $request->input('purpose'),
            'date_needed' => $request->input('date_needed'),
            'return_date' => $request->input('return_date') ?: null,
            'updated_at' => now(),
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $values['attachment_path'] = $file->store('facility-attachments', 'public');
            $values['attachment_name'] = $file->getClientOriginalName();
            if ($item->attachment_path) {
                Storage::disk('public')->delete($item->attachment_path); // ganti file -> hapus yang lama
            }
        }

        DB::table('facility_requests')->where('id', $facilityRequest)->update($values);

        return response()->json([
            'message' => 'Pengajuan fasilitas berhasil diperbarui.',
            'data' => $this->present($this->baseQuery()->where('facility_requests.id', $facilityRequest)->first(), $user),
        ]);
    }

    /** Guru memproses pengajuan (setujui / tolak / dipinjam / dikembalikan). */
    public function status(Request $request, int $facilityRequest): JsonResponse
    {
        $user = $this->profile();
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'teacher_note' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $decided = in_array($data['status'], ['disetujui', 'ditolak'], true);
        $updated = DB::table('facility_requests')->where('id', $facilityRequest)->update([
            'status' => $data['status'],
            'teacher_note' => $data['teacher_note'] ?? null,
            'approved_by' => $decided ? $user['id'] : null,
            'approved_at' => $decided ? now() : null,
            'updated_at' => now(),
        ]);

        abort_unless($updated, 404, 'Pengajuan tidak ditemukan.');

        return response()->json([
            'message' => 'Status pengajuan berhasil diperbarui.',
            'data' => $this->present($this->baseQuery()->where('facility_requests.id', $facilityRequest)->first(), $user),
        ]);
    }

    public function destroy(int $facilityRequest): JsonResponse
    {
        $user = $this->profile();
        $query = DB::table('facility_requests')->where('id', $facilityRequest);

        if ($user['role'] === 'siswa') {
            $query->where('student_id', $user['id'])->where('status', 'menunggu');
        }

        $item = $query->first();
        abort_unless($item, 404, 'Pengajuan tidak ditemukan atau sudah diproses.');

        if ($item->attachment_path) {
            Storage::disk('public')->delete($item->attachment_path); // file fisik ikut terhapus
        }
        DB::table('facility_requests')->where('id', $facilityRequest)->delete();

        return response()->json(['message' => 'Pengajuan fasilitas berhasil dihapus.']);
    }

    /** Membuka kembali file lampiran (inline) - hanya pemilik pengajuan atau guru. */
    public function attachment(int $facilityRequest)
    {
        $user = $this->profile();
        $item = DB::table('facility_requests')->where('id', $facilityRequest)->first();

        abort_unless($item && $item->attachment_path, 404, 'Lampiran tidak ada.');
        abort_unless($user['role'] === 'guru' || $item->student_id == $user['id'], 403);
        abort_unless(Storage::disk('public')->exists($item->attachment_path), 404, 'File tidak ditemukan di storage.');

        return Storage::disk('public')->response($item->attachment_path, $item->attachment_name);
    }

    private function baseQuery()
    {
        return DB::table('facility_requests')
            ->join('facilities', 'facilities.id', '=', 'facility_requests.facility_id')
            ->join('students', 'students.id', '=', 'facility_requests.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->select(
                'facility_requests.*',
                'facilities.name as facility',
                'students.student_code',
                'users.name as student_name'
            );
    }

    private function present(object $row, array $user): array
    {
        $pending = $row->status === 'menunggu';
        $own = $user['role'] === 'siswa' && $row->student_id == $user['id'];

        return [
            'id' => $row->id,
            'facility_id' => $row->facility_id,
            'facility' => $row->facility,
            'student_name' => $row->student_name,
            'student_code' => $row->student_code,
            'purpose' => $row->purpose,
            'date_needed' => $row->date_needed,
            'return_date' => $row->return_date,
            'status' => $row->status,
            'teacher_note' => $row->teacher_note,
            'created_at' => $row->created_at,
            'attachment_name' => $row->attachment_name,
            'attachment_url' => $row->attachment_path ? route('facilities.attachment', $row->id) : null,
            'can_edit' => $own && $pending,
            'can_delete' => $user['role'] === 'guru' || ($own && $pending),
            'can_review' => $user['role'] === 'guru',
        ];
    }
}
