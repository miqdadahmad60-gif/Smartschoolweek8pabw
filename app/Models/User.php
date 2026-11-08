<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'address', 'profile_photo', 'is_active'];
    protected $hidden = ['password', 'remember_token'];

    /**
     * Cast 'hashed' membuat setiap password yang diisi ke model
     * otomatis di-hash (bcrypt) sebelum disimpan ke database.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Profil lengkap sesuai peran (siswa / guru / ortu) yang dipakai oleh
     * controller & view portal. Kolom `id` pada profil adalah id siswa/guru/ortu.
     */
    public function profile(): array
    {
        $row = $this->attributesToArray();
        unset($row['password'], $row['remember_token']);
        $row['user_id'] = $this->id;

        if ($this->role === 'siswa') {
            $p = DB::table('students')->where('user_id', $this->id)->first();
            if ($p) $row = array_merge($row, (array) $p, ['user_id' => $this->id]);
        } elseif ($this->role === 'guru') {
            $p = DB::table('teachers')->where('user_id', $this->id)->first();
            if ($p) $row = array_merge($row, (array) $p, ['user_id' => $this->id, 'subject' => $p->main_subject]);
        } elseif ($this->role === 'ortu') {
            $p = DB::table('parents')->where('user_id', $this->id)->first();
            if ($p) {
                $code = DB::table('parent_students')
                    ->join('students', 'students.id', '=', 'parent_students.student_id')
                    ->where('parent_id', $p->id)->value('student_code');
                $row = array_merge($row, (array) $p, ['user_id' => $this->id, 'child_code' => $code ?? '']);
            }
        }

        return $row;
    }
}
