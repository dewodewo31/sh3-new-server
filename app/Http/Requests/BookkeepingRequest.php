<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookkeepingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'in:sponsor,event_income,other'],
            'sponsor_id' => ['nullable', 'required_if:category,sponsor', 'exists:sponsors,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'receipt' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal wajib diisi.',
            'transaction_date.date' => 'Tanggal tidak valid.',
            'description.required' => 'Keterangan wajib diisi.',
            'type.required' => 'Tipe wajib diisi.',
            'type.in' => 'Tipe tidak valid.',
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.numeric' => 'Jumlah harus berupa angka.',
            'amount.min' => 'Jumlah tidak boleh kurang dari 0.',
            'category.required' => 'Kategori wajib diisi.',
            'category.in' => 'Kategori tidak valid.',
            'sponsor_id.required_if' => 'Sponsor wajib diisi saat kategori sponsor dipilih.',
            'sponsor_id.exists' => 'Sponsor tidak ditemukan.',
            'event_id.exists' => 'Event tidak ditemukan.',
            'receipt.image' => 'Bukti nota harus berupa gambar.',
            'receipt.max' => 'Ukuran bukti nota maksimal 5MB.',
        ];
    }
}
