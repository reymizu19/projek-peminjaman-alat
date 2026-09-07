@extends('layouts.app')

@section('title', 'Laporan - Petugas')
@section('header-title', 'Laporan Peminjaman')

@section('content')
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Laporan Peminjaman</h3>
            <div class="flex w-full md:w-auto items-center gap-2">
                <form action="{{ route('petugas.laporan.index') }}" method="GET" class="flex flex-col md:flex-row w-full md:w-auto items-center gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama peminjam..."
                        class="w-full md:w-56 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                        class="w-full md:w-44 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" title="Tanggal mulai">
                    <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}"
                        class="w-full md:w-44 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" title="Tanggal selesai">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-lg transition">
                        Cari
                    </button>
                    <a href="{{ route('petugas.laporan.index') }}" class="inline-flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 text-sm font-semibold rounded-lg transition">
                        Reset
                    </a>
                </form>
                <a href="{{ route('petugas.laporan.pdf', ['search' => request('search'), 'tanggal_mulai' => request('tanggal_mulai'), 'tanggal_selesai' => request('tanggal_selesai')]) }}" target="_blank"
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-semibold rounded-lg transition shadow-sm">
                    <span>🖨️</span>
                    <span>Cetak PDF</span>
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="px-5 pt-4">
                <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Status</th>
                        <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                        <th class="py-3 px-4 border-b">Rencana Kembali</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                {{ $item->user->name ?? 'User Dihapus' }}
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
                                {{ $item->tanggal_pinjam ? \Carbon\Carbon::parse($item->tanggal_pinjam)->format('d-m-Y H:i:s') : '-' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                {{ $item->tanggal_kembali_plan ? \Carbon\Carbon::parse($item->tanggal_kembali_plan)->format('d-m-Y H:i:s') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">Belum ada data laporan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
