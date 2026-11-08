<?php

namespace Modules\Facility\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pengajuan fasilitas (dipakai store & update). Dipanggil lewat AJAX,
 * sehingga kegagalan validasi menghasilkan respons JSON 422.
 */
class FacilityRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware role:siswa pada route
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'purpose' => ['required', 'string', 'max:1000'],
            'date_needed' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after_or_equal:date_needed'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // 2 MB
        ];
    }

    public function messages(): array
    {
        return [
            'facility_id.required' => 'Fasilitas wajib dipilih.',
            'facility_id.exists' => 'Fasilitas tidak ditemukan.',
            'purpose.required' => 'Tujuan penggunaan wajib diisi.',
            'purpose.max' => 'Tujuan maksimal 1000 karakter.',
            'date_needed.required' => 'Tanggal penggunaan wajib diisi.',
            'date_needed.date' => 'Tanggal penggunaan tidak valid.',
            'date_needed.after_or_equal' => 'Tanggal penggunaan tidak boleh sebelum hari ini.',
            'return_date.date' => 'Tanggal pengembalian tidak valid.',
            'return_date.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal penggunaan.',
            'attachment.file' => 'Lampiran harus berupa file.',
            'attachment.mimes' => 'Lampiran harus berformat JPG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran lampiran maksimal 2 MB.',
        ];
    }
}
