<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PeminjamProfilController extends Controller
{
    public function edit()
    {
        return view('peminjam.profil.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'foto_profil' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }

            $data['foto_profil'] = $request->file('foto_profil')->store('profil', 'public');
        }

        $user->update($data);

        return redirect()->route('peminjam.profil.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
