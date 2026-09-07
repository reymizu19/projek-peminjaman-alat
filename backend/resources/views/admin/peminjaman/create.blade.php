@extends('layouts.app')

@section('title', 'Tambah Peminjaman - Panel Admin')
@section('header-title', 'Manajemen Transaksi Peminjaman')

@section('content')
    <div class="space-y-4">
        <div class="bg-white border border-gray-300 rounded-lg shadow-sm p-5 max-w-2xl">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Form Tambah Transaksi Peminjaman</h3>

            <form action="{{ route('admin.peminjaman.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Peminjam (User)</label>
                        <select name="user_id" id="user_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Pilih User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="tanggal_pinjam" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pinjam</label>
                            <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label for="tanggal_kembali_plan" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Rencana Kembali</label>
                            <input type="date" name="tanggal_kembali_plan" id="tanggal_kembali_plan" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Daftar Alat Yang Dipinjam</label>

                        <div class="border border-gray-300 rounded-md p-3 bg-gray-50">
                            <div id="alat-list">
                                <div class="alat-row flex items-center gap-2 mb-3">
                                <select name="alat_id[]" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Pilih Alat</option>
                                    @foreach($alats as $alat)
                                        <option value="{{ $alat->id }}">{{ $alat->nama_alat }} (Stok: {{ $alat->stok }})</option>
                                    @endforeach
                                </select>
                                <input type="number" name="jumlah[]" value="1" min="1" class="w-24 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <button type="button" class="text-red-500 hover:text-red-700 text-lg font-bold" title="Hapus alat">×</button>
                                </div>
                            </div>

                            <button type="button" id="tambah-alat" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-md transition">
                                + Tambah Alat Lain
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('admin.peminjaman.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-medium transition">
                            Batal
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                            Simpan Peminjaman
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const alatList = document.getElementById('alat-list');
        const tambahAlatButton = document.getElementById('tambah-alat');

        tambahAlatButton.addEventListener('click', () => {
            const firstRow = alatList.querySelector('.alat-row');
            const newRow = firstRow.cloneNode(true);
            newRow.querySelector('select').value = '';
            newRow.querySelector('input').value = 1;
            alatList.appendChild(newRow);
        });

        alatList.addEventListener('click', (event) => {
            if (!event.target.matches('button')) {
                return;
            }

            const rows = alatList.querySelectorAll('.alat-row');
            if (rows.length > 1) {
                event.target.closest('.alat-row').remove();
            }
        });
    </script>
@endsection