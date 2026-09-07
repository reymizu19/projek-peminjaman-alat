@extends('layouts.app')

@section('title', 'Laporan - Petugas')
@section('header-title', 'Laporan Peminjaman')

@section('content')
    <div class="space-y-5">
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Filter Laporan</h3>
            </div>

            <div class="p-5">
                <form action="{{ route('petugas.laporan.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari Nama Peminjam</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama peminjam..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Peminjaman</label>
                        <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="semua" {{ request('status', 'semua') == 'semua' ? 'selected' : '' }}>Semua Status</option>
                            <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                            <option value="dipinjam" {{ request('status') == 'dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                            <option value="dikembalikan" {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>Dikembalikan</option>
                            <option value="telat" {{ request('status') == 'telat' ? 'selected' : '' }}>Telat</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div class="md:col-span-5 flex justify-end gap-2">
                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-lg transition">
                            Filter
                        </button>
                        <a href="{{ route('petugas.laporan.index') }}" class="inline-flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 text-sm font-semibold rounded-lg transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-4">
                <h3 class="text-lg font-bold text-gray-800">Hasil Rekap Laporan</h3>
                <a href="{{ route('petugas.laporan.pdf', ['search' => request('search'), 'status' => request('status', 'semua'), 'tanggal_mulai' => request('tanggal_mulai'), 'tanggal_selesai' => request('tanggal_selesai')]) }}" target="_blank"
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-semibold rounded-lg transition shadow-sm">
                    <span>🖨️</span>
                    <span>Cetak / Print Laporan</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider">
                            <th class="py-3 px-4 border-b">No</th>
                            <th class="py-3 px-4 border-b">Peminjam</th>
                            <th class="py-3 px-4 border-b">Tgl Pinjam</th>
                            <th class="py-3 px-4 border-b">Rencana Kembali</th>
                            <th class="py-3 px-4 border-b">Status</th>
                            <th class="py-3 px-4 border-b">Detail Alat</th>
                            <th class="py-3 px-4 border-b">Denda</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($peminjamans as $index => $item)
                            <tr class="hover:bg-gray-50 transition align-top">
                                <td class="py-3 px-4 border-b font-medium text-gray-900">{{ $index + 1 }}</td>
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
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                        @if($item->status == 'diajukan') bg-yellow-100 text-yellow-800
                                        @elseif($item->status == 'dipinjam') bg-blue-100 text-blue-800
                                        @elseif($item->status == 'dikembalikan') bg-emerald-100 text-emerald-800
                                        @elseif($item->status == 'telat') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 border-b">
                                    <div class="space-y-1">
                                        @forelse($item->detailPinjams as $detail)
                                            <div>• {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }} ({{ $detail->jumlah }})</div>
                                        @empty
                                            <div>-</div>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="py-3 px-4 border-b">
                                    Rp {{ number_format($item->pengembalian?->denda ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500">Belum ada data laporan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
