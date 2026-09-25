@extends('layouts.app')

@section('title', 'Edit Pengajuan - Peminjam')
@section('header-title', 'Edit Pengajuan Peminjaman')

@section('content')
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Perbaiki Data Pengajuan #{{ $peminjaman->id }}</h3>
            <p class="mt-1 text-sm text-gray-500">Perubahan hanya dapat dilakukan sebelum pengajuan disetujui petugas.</p>
        </div>

        <form action="{{ route('peminjam.peminjaman.update', $peminjaman) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-gray-200">
                <div>
                    <label for="tgl_kembali_plan" class="block text-gray-700 text-sm font-semibold mb-2">Rencana Tanggal Kembali</label>
                    <input id="tgl_kembali_plan" type="date" name="tgl_kembali_plan" value="{{ old('tgl_kembali_plan', optional($peminjaman->tanggal_kembali_plan)->format('Y-m-d')) }}" min="{{ now()->addDay()->format('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="alasan" class="block text-gray-700 text-sm font-semibold mb-2">Alasan Peminjaman</label>
                    <input id="alasan" type="text" name="alasan" value="{{ old('alasan', $peminjaman->alasan) }}" maxlength="500" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                            <th class="py-3 px-4 border-b">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($alats as $alat)
                            @php($detail = $selected->get($alat->id))
                            <tr class="hover:bg-gray-50 transition align-middle">
                                <td class="py-3 px-4 border-b"><input type="checkbox" name="alat_id[]" value="{{ $alat->id }}" class="h-4 w-4 text-blue-600" @checked($detail)></td>
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
                                <td class="py-3 px-4 border-b"><input type="number" name="jumlah[{{ $alat->id }}]" value="{{ old('jumlah.' . $alat->id, $detail->jumlah ?? 1) }}" min="1" max="{{ $alat->stok }}" class="w-20 px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-500">Tidak ada alat tersedia.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
                <a href="{{ route('peminjam.riwayat') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">Batal</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
