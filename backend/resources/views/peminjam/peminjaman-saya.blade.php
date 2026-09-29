@extends('layouts.app')

@section('title', 'Peminjaman Saya - Peminjam')
@section('header-title', 'Peminjaman Saya')

@section('content')
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Daftar Peminjaman Saya</h3>
            <p class="mt-1 text-sm text-gray-500">Daftar Pengajuan dan Peminjaman Alat Anda.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjaman</th>
                        <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                        <th class="py-3 px-4 border-b">Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">ALASAN PENGAJUAN</th>
                        <th class="py-3 px-4 border-b">Detail Alat</th>
                        <th class="py-3 px-4 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $peminjaman)
                        <tr class="hover:bg-gray-50 transition align-top">
                            @php
                                $tanggalKembaliPlan = $peminjaman->tanggal_kembali_plan
                                    ? \Carbon\Carbon::parse($peminjaman->tanggal_kembali_plan)->startOfDay()
                                    : null;
                                $sudahTerlambat = $peminjaman->status === 'dipinjam'
                                    && $tanggalKembaliPlan
                                    && now()->startOfDay()->greaterThan($tanggalKembaliPlan);
                                $hariTersisa = $peminjaman->status === 'dipinjam' && $tanggalKembaliPlan
                                    ? now()->startOfDay()->diffInDays($tanggalKembaliPlan, false)
                                    : null;
                            @endphp
                            <td class="py-3 px-4 border-b font-medium text-gray-900">
                                #{{ $peminjaman->id }}
                                <span class="block mt-1 text-xs font-semibold {{ $sudahTerlambat ? 'text-red-700' : 'font-normal text-gray-500' }}">Status: {{ $sudahTerlambat ? 'Telat' : ($peminjaman->status === 'menunggu_pengembalian' ? 'MengajukanPengembalian' : ucfirst(str_replace('_', ' ', $peminjaman->status))) }}</span>
                                @if($sudahTerlambat)
                                    <span class="mt-2 block rounded border border-red-200 bg-red-50 px-2 py-1.5 text-xs font-medium text-red-800">
                                        Tanggal pengembalian telah lewat. Mohon segera kembalikan alat.
                                    </span>
                                @endif
                                @if($peminjaman->pengingat_pengembalian_at)
                                    <span class="mt-2 block rounded border border-amber-200 bg-amber-50 px-2 py-1.5 text-xs font-medium text-amber-800">
                                        Peringatan petugas: alat sudah jatuh tempo. Mohon segera kembalikan.
                                        ({{ $peminjaman->pengingat_pengembalian_at->format('d-m-Y H:i') }})
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b">
                                {{ $peminjaman->tanggal_pinjam ? \Carbon\Carbon::parse($peminjaman->tanggal_pinjam)->format('d-m-Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                {{ $peminjaman->tanggal_kembali_plan ? \Carbon\Carbon::parse($peminjaman->tanggal_kembali_plan)->format('d-m-Y') : '-' }}
                                @if($hariTersisa !== null)
                                    <span class="mt-1 block text-xs font-semibold {{ $hariTersisa < 0 ? 'text-red-600' : ($hariTersisa === 0 ? 'text-amber-600' : 'text-blue-600') }}">
                                        {{ $hariTersisa < 0 ? 'Terlambat ' . abs($hariTersisa) . ' hari' : ($hariTersisa === 0 ? 'Jatuh tempo hari ini' : 'Tersisa ' . $hariTersisa . ' hari') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b max-w-xs">
                                {{ $peminjaman->alasan ?: '-' }}
                            </td>
                            <td class="py-3 px-4 border-b">
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    @foreach($peminjaman->detailPinjams as $detail)
                                        <li>
                                            <span class="font-semibold">{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}</span>
                                            (Jumlah: {{ $detail->jumlah }})
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="py-3 px-4 border-b text-center">
                                @if($peminjaman->status === 'diajukan')
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('peminjam.peminjaman.edit', $peminjaman) }}" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">Edit</a>
                                        <form action="{{ route('peminjam.peminjaman.destroy', $peminjaman) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">Hapus</button>
                                        </form>
                                    </div>
                                @elseif($peminjaman->status === 'dipinjam')
                                    <form action="{{ route('peminjam.peminjaman.kembalikan', $peminjaman) }}" method="POST">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Ajukan pengembalian alat untuk diperiksa petugas?')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-semibold transition">Kembalikan Alat</button>
                                    </form>
                                @else
                                    <span class="text-xs font-semibold text-amber-700 bg-amber-50 px-2.5 py-1 rounded">MengajukanPengembalian</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-500">Belum ada peminjaman aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
