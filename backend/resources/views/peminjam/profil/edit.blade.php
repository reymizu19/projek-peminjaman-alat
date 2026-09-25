@extends('layouts.app')

@section('title', 'Profil Saya - Peminjam')
@section('header-title', 'Profil Saya')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">{{ $errors->first() }}</div>
    @endif

    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('peminjam.profil.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-6 flex items-center gap-4">
                @if($user->foto_profil)
                    <img src="{{ Storage::disk('public')->url($user->foto_profil) }}" alt="Foto profil {{ $user->name }}" class="h-20 w-20 rounded-full border border-gray-200 object-cover">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 text-2xl font-bold text-gray-500">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                @endif
                <div>
                    <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                </div>
            </div>

            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-semibold mb-2">Nama Lengkap</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="no_hp" class="block text-gray-700 text-sm font-semibold mb-2">No. HP</label>
                <input id="no_hp" type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="alamat" class="block text-gray-700 text-sm font-semibold mb-2">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('alamat', $user->alamat) }}</textarea>
            </div>

            <div class="mb-6">
                <label for="foto_profil" class="block text-gray-700 text-sm font-semibold mb-2">Foto Profil</label>
                <input id="foto_profil" type="file" name="foto_profil" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-gray-400">Format gambar, maksimal 2 MB.</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Simpan Profil</button>
            </div>
        </form>
    </div>
@endsection
