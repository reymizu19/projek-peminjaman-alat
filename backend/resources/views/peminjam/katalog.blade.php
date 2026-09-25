@extends('layouts.app')

@section('title', 'Katalog Alat - Panel Peminjam')
@section('header-title', 'Katalog Alat')

@section('content')
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Alat Laboratorium</h3>
            <form action="{{ route('peminjam.katalog') }}" method="GET" class="flex items-center gap-2 w-full md:w-auto">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama alat..." class="w-full md:w-64 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select name="kategori" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua kategori</option>
                    @foreach($kategoris as $item)
                        <option value="{{ $item->id }}" @selected((string) ($kategori ?? '') === (string) $item->id)>{{ $item->nama_kategori }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-lg transition">Cari</button>
            </form>
        </div>

        <form action="{{ route('peminjam.peminjaman.ajukan') }}" method="POST">
            @csrf
            <div class="p-5 border-b border-gray-200 bg-white grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tgl_kembali_plan" class="block text-gray-700 text-sm font-semibold mb-2">Rencana Tanggal Kembali</label>
                    <input id="tgl_kembali_plan" type="date" name="tgl_kembali_plan" min="{{ now()->addDay()->format('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="alasan" class="block text-gray-700 text-sm font-semibold mb-2">Alasan Peminjaman</label>
                    <input id="alasan" type="text" name="alasan" maxlength="500" required placeholder="Contoh: Praktik kelas" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 border-b">Pilih</th>
                            <th class="py-3 px-4 border-b">Gambar</th>
                            <th class="py-3 px-4 border-b">Nama Alat</th>
                            <th class="py-3 px-4 border-b">Kategori</th>
                            <th class="py-3 px-4 border-b">Stok</th>
                            <th class="py-3 px-4 border-b">Status</th>
                            <th class="py-3 px-4 border-b">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($alats as $alat)
                            <tr class="hover:bg-gray-50 transition align-middle">
                                <td class="py-3 px-4 border-b"><input type="checkbox" name="alat_id[]" value="{{ $alat->id }}" class="alat-check h-4 w-4 text-blue-600" @disabled($alat->stok < 1)></td>
                                <td class="py-3 px-4 border-b">
                                    @if($alat->gambar)
                                        <img src="{{ Storage::disk('public')->url($alat->gambar) }}" alt="{{ $alat->nama_alat }}" class="w-14 h-14 object-cover rounded-lg border border-gray-200">
                                    @else
                                        <div class="w-14 h-14 rounded-lg border border-dashed border-gray-300 flex items-center justify-center text-[10px] text-gray-400 text-center">Tidak ada foto</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 border-b font-medium text-gray-900">{{ $alat->nama_alat }}</td>
                                <td class="py-3 px-4 border-b">{{ $alat->kategori->nama_kategori ?? '-' }}</td>
                                <td class="py-3 px-4 border-b font-semibold">{{ $alat->stok }}</td>
                                <td class="py-3 px-4 border-b"><span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $alat->stok > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">{{ $alat->stok > 0 ? 'Tersedia' : 'Sedang dipinjam' }}</span></td>
                                <td class="py-3 px-4 border-b"><input type="number" name="jumlah[{{ $alat->id }}]" value="1" min="1" max="{{ $alat->stok }}" {{ $alat->stok < 1 ? 'disabled' : '' }} class="w-20 px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-gray-500">Belum ada data alat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                <span class="text-sm text-gray-500"><span id="selected-count">0</span> alat dipilih</span>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">Ajukan Peminjaman</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('.alat-check').forEach((checkbox) => checkbox.addEventListener('change', () => {
            document.getElementById('selected-count').textContent = document.querySelectorAll('.alat-check:checked').length;
        }));
    </script>
@endsection
