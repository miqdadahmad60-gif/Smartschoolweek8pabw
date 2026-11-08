<?php

namespace Modules\Material\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Modul Material - CRUD SYNCHRONOUS (form biasa + redirect) dengan upload file.
 * Akses dijaga middleware `role` pada routes/web.php modul ini.
 */
class MaterialController extends Controller
{
    private function profile(): array
    {
        return Auth::user()->profile();
    }

    private function requireTeacher(): array
    {
        $user = $this->profile();
        abort_unless(($user['role'] ?? null) === 'guru', 403, 'Hanya guru yang dapat mengelola materi.');
        return $user;
    }

    private function requireReader(): array
    {
        $user = $this->profile();
        abort_unless(in_array($user['role'] ?? null, ['guru', 'siswa'], true), 403);
        return $user;
    }

    public function index()
    {
        $user = $this->requireReader();
        $materials = DB::table('materials')
            ->join('subjects', 'subjects.id', '=', 'materials.subject_id')
            ->join('teachers', 'teachers.id', '=', 'materials.teacher_id')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->where('materials.is_published', 1)
            ->select('materials.*', 'subjects.name as subject', 'teachers.id as teacher_id', 'users.name as teacher_name')
            ->latest('materials.created_at')
            ->get();

        return view('material::index', [
            'user' => $user,
            'active' => 'academic',
            'page_title' => 'Kelola Materi',
            'materials' => $materials,
        ]);
    }

    public function create()
    {
        $user = $this->requireTeacher();
        return view('material::create', ['user' => $user, 'active' => 'academic', 'page_title' => 'Tambah Materi']);
    }

    public function store(Request $request)
    {
        $user = $this->requireTeacher();
        $data = $this->validateMaterial($request, true);
        $subjectId = $this->subjectId($data['subject'], $user['id']);
        $file = $request->file('material_file');
        $path = $file->store('materials', 'public');

        DB::table('materials')->insert([
            'subject_id' => $subjectId,
            'teacher_id' => $user['id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_name' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
            'is_published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('materials.index')->with('success', 'Materi dan file berhasil ditambahkan.');
    }

    public function show(int $material)
    {
        $user = $this->requireReader();
        $item = $this->findMaterial($material);
        abort_unless($item && $item->is_published, 404);

        return view('material::show', [
            'user' => $user,
            'active' => 'academic',
            'page_title' => 'Detail Materi',
            'material' => $item,
        ]);
    }

    public function edit(int $material)
    {
        $user = $this->requireTeacher();
        $item = $this->findMaterial($material);
        abort_unless($item && $item->teacher_id == $user['id'] && $item->is_published, 404);

        return view('material::edit', [
            'user' => $user,
            'active' => 'academic',
            'page_title' => 'Edit Materi',
            'material' => $item,
        ]);
    }

    public function update(Request $request, int $material)
    {
        $user = $this->requireTeacher();
        $item = DB::table('materials')->where('id', $material)->where('teacher_id', $user['id'])->where('is_published', 1)->first();
        abort_unless($item, 404);

        $data = $this->validateMaterial($request, false);
        $subjectId = $this->subjectId($data['subject'], $user['id']);
        $values = [
            'subject_id' => $subjectId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'updated_at' => now(),
        ];

        if ($request->hasFile('material_file')) {
            $file = $request->file('material_file');
            $newPath = $file->store('materials', 'public');
            if ($item->file_name) {
                Storage::disk('public')->delete($item->file_name);
            }
            $values += [
                'file_name' => $newPath,
                'original_name' => $file->getClientOriginalName(),
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
            ];
        }

        DB::table('materials')->where('id', $material)->update($values);

        return redirect()->route('materials.show', $material)->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(int $material)
    {
        $user = $this->requireTeacher();
        $item = DB::table('materials')->where('id', $material)->where('teacher_id', $user['id'])->first();
        abort_unless($item, 404);

        if ($item->file_name) {
            Storage::disk('public')->delete($item->file_name);
        }

        DB::table('materials')->where('id', $material)->delete();

        return redirect()->route('materials.index')->with('success', 'Materi dan file berhasil dihapus.');
    }

    public function download(int $material)
    {
        $this->requireReader();
        $item = DB::table('materials')->where('id', $material)->where('is_published', 1)->first();
        abort_unless($item && $item->file_name && Storage::disk('public')->exists($item->file_name), 404);

        return Storage::disk('public')->download($item->file_name, $item->original_name ?: basename($item->file_name));
    }

    private function validateMaterial(Request $request, bool $fileRequired): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'subject' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'material_file' => array_merge(
                $fileRequired ? ['required'] : ['nullable'],
                ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip', 'max:10240']
            ),
        ], [
            'material_file.required' => 'File materi wajib diunggah.',
            'material_file.mimes' => 'Format file harus JPG, JPEG, PNG, PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, atau ZIP.',
            'material_file.max' => 'Ukuran file maksimal 10 MB.',
        ]);
    }

    private function subjectId(string $name, int $teacherId): int
    {
        $id = DB::table('subjects')->where('name', $name)->value('id');
        if ($id) return (int) $id;

        return DB::table('subjects')->insertGetId([
            'subject_code' => strtoupper(substr(md5($name . $teacherId), 0, 10)),
            'name' => $name,
            'description' => 'Mata pelajaran Smart School Kupang',
            'teacher_id' => $teacherId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function findMaterial(int $id): ?object
    {
        return DB::table('materials')
            ->join('subjects', 'subjects.id', '=', 'materials.subject_id')
            ->join('teachers', 'teachers.id', '=', 'materials.teacher_id')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->where('materials.id', $id)
            ->select('materials.*', 'subjects.name as subject', 'teachers.id as teacher_id', 'users.name as teacher_name')
            ->first();
    }
}
