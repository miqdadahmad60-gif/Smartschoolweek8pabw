<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Membuat 6 akun dummy (3 siswa, 2 guru, 1 orang tua) memakai UserFactory + Faker.
 * Nama, telepon, dan alamat dihasilkan Faker; email dibuat tetap agar mudah dipakai
 * menguji login. Password seluruh akun dummy: password123 (disimpan dalam bentuk hash bcrypt).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $classes = ['X TKJ 1', 'XI TKJ 1', 'XII TKJ 2'];
        $subjects = ['Bahasa Inggris', 'Pemrograman Web'];

        // --- 3 siswa
        $students = [];
        foreach ($classes as $i => $class) {
            $n = $i + 1;
            $user = User::factory()->role('siswa')->create(['email' => "siswa{$n}@smartschool.test"]);
            DB::table('students')->insert([
                'user_id'      => $user->id,
                'student_code' => 'SSK' . str_pad((string) (DB::table('students')->max('id') + 1), 3, '0', STR_PAD_LEFT),
                'nisn'         => fake()->unique()->numerify('00########'),
                'class_name'   => $class,
                'major'        => 'Teknik Komputer dan Jaringan',
                'school_year'  => '2026/2027',
            ]);
            $students[] = DB::table('students')->where('user_id', $user->id)->value('id');
        }

        // --- 2 guru
        foreach ($subjects as $i => $subject) {
            $n = $i + 1;
            $user = User::factory()->role('guru')->create(['email' => "guru{$n}@smartschool.test"]);
            DB::table('teachers')->insert([
                'user_id'      => $user->id,
                'teacher_code' => 'GRU' . str_pad((string) (DB::table('teachers')->max('id') + 1), 3, '0', STR_PAD_LEFT),
                'nip'          => fake()->unique()->numerify('19##############'),
                'main_subject' => $subject,
            ]);
        }

        // --- 1 orang tua (terhubung ke siswa pertama dari seeder ini)
        $user = User::factory()->role('ortu')->create(['email' => 'ortu1@smartschool.test']);
        $parentId = DB::table('parents')->insertGetId([
            'user_id'      => $user->id,
            'parent_code'  => 'ORT' . str_pad((string) (DB::table('parents')->max('id') + 1), 3, '0', STR_PAD_LEFT),
            'relationship' => fake()->randomElement(['ayah', 'ibu', 'wali']),
        ]);
        DB::table('parent_students')->insert(['parent_id' => $parentId, 'student_id' => $students[0]]);
    }
}
