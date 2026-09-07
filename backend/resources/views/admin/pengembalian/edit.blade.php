@extends('layouts.app')

@section('title', 'Edit Pengembalian - Panel Admin')
@section('header-title', 'Edit Data Pengembalian')

@section('content')
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="mb-5 pb-4 border-b border-gray-200 text-sm text-gray-600">
            <p><span class="font-semibold">Peminjam:</span> {{ $pengembalian->peminjaman->user->name ?? 'User Dihapus' }}</p>
            <p><span class="font-semibold">Tanggal kembali:</span> {{ $pengembalian->tanggal_kembali ? \Carbon\Carbon::parse($pengembalian->tanggal_kembali)->format('d-m-Y H:i:s') : '-' }}</p>
            <p><span class="font-semibold">Petugas:</span> {{ $pengembalian->petugas->name ?? 'Petugas Dihapus' }}</p>
        </div>

        <form action="{{ route('admin.pengembalian.update', $pengembalian->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-semibold mb-2">Kondisi Alat Saat Kembali</label>
                <input type="text" name="kondisi_kembali" value="{{ old('kondisi_kembali', $pengembalian->kondisi_kembali) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('kondisi_kembali') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-semibold mb-2">Denda</label>
                <input type="number" name="denda" id="denda" min="0" value="{{ old('denda', $pengembalian->denda) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('denda') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                <span id="denda-warning" class="hidden text-red-500 text-xs">Biaya denda terlalu besar!</span>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('admin.pengembalian.index') }}"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">Batal</a>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Perbarui</button>
            </div>
        </form>
    </div>

    <script>
        const dendaInput = document.getElementById('denda');
        const dendaWarning = document.getElementById('denda-warning');
        const dendaForm = dendaInput.form;

        function validateDenda() {
            const terlaluBesar = (dendaInput.value.match(/0/g) || []).length > 8;

            dendaWarning.classList.toggle('hidden', !terlaluBesar);
            return !terlaluBesar;
        }

        dendaInput.addEventListener('input', validateDenda);
        dendaForm.addEventListener('submit', function (event) {
            if (!validateDenda()) {
                event.preventDefault();
            }
        });
    </script>
@endsection
