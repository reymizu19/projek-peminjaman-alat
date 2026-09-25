<?php 
 
namespace App\Http\Requests\Pengembalian; 
 
use Illuminate\Foundation\Http\FormRequest; 
 
class UpdatePengembalianRequest extends FormRequest 
{ 
    public function authorize(): bool 
    { 
        return true; 
    } 
 
    public function rules(): array 
    { 
        return [ 
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
            'kondisi_kembali' => 'Kondisi barang kembali', 
            'denda' => 'Nilai denda', 
        ]; 
    } 
} 