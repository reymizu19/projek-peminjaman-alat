@extends('layouts.app')

@section('title', 'Pemantauan Pengembalian - Petugas')
@section('header-title', 'Pemantauan Pengembalian')

@section('content')
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Peminjaman Aktif (Belum Kembali)</h3>
            <form action="{{ route('petugas.pengembalian.index') }}" method="GET" class="flex w-full md:w-auto items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama peminjam..."
                    class="w-full md:w-72 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-lg transition">
                    Cari
                </button>
            </form>
        </div>

        @if($errors->any())
            <div class="px-5 mt-4">
                <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Tgl Pinjam</th>
                        <th class="py-3 px-4 border-b">Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">Status</th>
                        <th class="py-3 px-4 border-b">Detail Alat</th>
                        <th class="py-3 px-4 border-b">Aksi Pengembalian</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $item)
                        <tr class="align-top hover:bg-gray-50 transition">
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $item->user->name ?? 'User Dihapus' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                {{ $item->tanggal_pinjam ? \Carbon\Carbon::parse($item->tanggal_pinjam)->format('Y-m-d') : '-' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                {{ $item->tanggal_kembali_plan ? \Carbon\Carbon::parse($item->tanggal_kembali_plan)->format('Y-m-d') : '-' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 border-b align-top">
                                <div class="space-y-1">
                                    @foreach($item->detailPinjams as $detail)
                                        <div class="text-gray-700">
                                            • {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }} (Jumlah: {{ $detail->jumlah }})
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-4 border-b align-top">
                                <form action="{{ route('petugas.pengembalian.terima', $item->id) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-gray-700">Kondisi Kembali</label>
                                        <select name="kondisi_kembali" class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            <option value="Baik">Baik</option>
                                            <option value="Rusak">Rusak</option>
                                            <option value="Hilang">Hilang</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-gray-700">Denda (Rp)</label>
                                        <input type="number" name="denda" value="0" min="0" class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    </div>
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-sm font-semibold rounded-md transition">
                                        Terima Pengembalian
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-500">Belum ada data peminjaman aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
