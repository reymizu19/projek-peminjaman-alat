@extends('layouts.app')

@section('title', 'Riwayat Peminjaman - Peminjam')
@section('header-title', 'Riwayat Peminjaman')

@section('content')
    <div class="space-y-5">
        <p class="text-sm text-slate-500">Riwayat peminjaman alat Anda.</p>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider">
                            <th class="py-3 px-4 border-b">No</th>
                            <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                            <th class="py-3 px-4 border-b">Rencana Kembali</th>
                            <th class="py-3 px-4 border-b">Tanggal Dikembalikan</th>
                            <th class="py-3 px-4 border-b">Detail Alat</th>
                            <th class="py-3 px-4 border-b">Kondisi Kembali</th>
                            <th class="py-3 px-4 border-b">Denda</th>
                            <th class="py-3 px-4 border-b">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($peminjamans as $index => $peminjaman)
                            <tr class="hover:bg-gray-50 transition align-top">
                                <td class="py-3 px-4 border-b font-medium text-gray-900">{{ $index + 1 }}</td>
                                <td class="py-3 px-4 border-b">{{ $peminjaman->tanggal_pinjam ? \Carbon\Carbon::parse($peminjaman->tanggal_pinjam)->format('d-m-Y') : '-' }}</td>
                                <td class="py-3 px-4 border-b">{{ $peminjaman->tanggal_kembali_plan ? \Carbon\Carbon::parse($peminjaman->tanggal_kembali_plan)->format('d-m-Y') : '-' }}</td>
                                <td class="py-3 px-4 border-b">{{ $peminjaman->pengembalian?->tanggal_kembali ? \Carbon\Carbon::parse($peminjaman->pengembalian->tanggal_kembali)->format('d-m-Y H:i') : '-' }}</td>
                                <td class="py-3 px-4 border-b">
                                    <div class="space-y-1">
                                        @forelse($peminjaman->detailPinjams as $detail)
                                            <div>{{ $detail->alat->nama_alat ?? 'Alat dihapus' }} ({{ $detail->jumlah }})</div>
                                        @empty
                                            <span>-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="py-3 px-4 border-b">{{ $peminjaman->pengembalian?->kondisi_kembali ?? '-' }}</td>
                                <td class="py-3 px-4 border-b font-medium">Rp {{ number_format($peminjaman->pengembalian?->denda ?? 0, 0, ',', '.') }}</td>
                                <td class="py-3 px-4 border-b">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $peminjaman->status === 'telat' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ ucfirst($peminjaman->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-gray-500">Belum ada riwayat peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection