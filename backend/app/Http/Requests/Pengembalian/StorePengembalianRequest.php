<?php 
namespace App\Http\Requests\Pengembalian; 
use Illuminate\Foundation\Http\FormRequest; 
use Illuminate\Validation\Rule; 
 
class StorePengembalianRequest extends FormRequest 
{ 
    public function authorize(): bool 
    { 
        return true; 
    } 
    public function rules(): array 
    { 
        return [ 
            'peminjaman_id' => ['required', 'integer', 
                Rule::exists('peminjaman', 'id') 
            ], 
            'kondisi_kembali' => ['required', 'string', 'max:255'], 
            'denda' => ['nullable', 'integer', 'min:0', function ($attribute, $value, $fail) {
                if (substr_count((string) $value, '0') > 8) {
                    $fail('Biaya denda terlalu besar!');
                }
            }], 
        ]; 
    } 
    public function attributes(): array 
    { 
        return [ 
            'peminjaman_id' => 'ID Peminjaman', 
            'kondisi_kembali' => 'Kondisi barang kembali', 
            'denda' => 'Nilai denda', 
        ]; 
    } 
} 