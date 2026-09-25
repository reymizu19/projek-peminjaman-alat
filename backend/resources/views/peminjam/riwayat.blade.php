@extends('layouts.app')

@section('title', 'Riwayat Peminjaman - Peminjam')
@section('header-title', 'Riwayat Peminjaman')

@section('content')
    <div class="space-y-5">
        <p class="text-sm text-slate-500">Daftar pengajuan dan status peminjaman Anda.</p>

        @forelse($peminjamans as $peminjaman)
            @php
                $statusStyles = [
                    'diajukan' => 'bg-amber-50 text-amber-700',
                    'dipinjam' => 'bg-blue-50 text-blue-700',
                    'dikembalikan' => 'bg-emerald-50 text-emerald-700',
                    'telat' => 'bg-red-50 text-red-700',
                ];
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Peminjaman #{{ $peminjaman->id }}</p>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ optional($peminjaman->tanggal_pinjam)->format('d M Y') }}</h2>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusStyles[$peminjaman->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($peminjaman->status) }}</span>
                </div>
                <div class="grid gap-4 py-4 text-sm md:grid-cols-3">
                    @php
                        $rencanaKembali = $peminjaman->tanggal_kembali_plan ?? $peminjaman->tgl_kembali_plan;
                        $rencanaKembali = $rencanaKembali ? \Carbon\Carbon::parse($rencanaKembali) : null;
                        $hariTersisa = $rencanaKembali ? now()->startOfDay()->diffInDays($rencanaKembali->copy()->startOfDay(), false) : null;
                    @endphp
                    <div>
                        <p class="text-xs text-slate-500">Rencana kembali</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $rencanaKembali ? $rencanaKembali->format('d M Y') : '-' }}</p>
                        @if($peminjaman->status === 'dipinjam' && $hariTersisa !== null)
                            <p class="mt-1 text-xs font-semibold {{ $hariTersisa < 0 ? 'text-red-600' : 'text-blue-600' }}">
                                {{ $hariTersisa < 0 ? 'Terlambat ' . abs($hariTersisa) . ' hari' : ($hariTersisa === 0 ? 'Batas pengembalian hari ini' : 'Tersisa ' . $hariTersisa . ' hari') }}
                            </p>
                        @elseif($peminjaman->status === 'diajukan')
                            <p class="mt-1 text-xs text-slate-400">Menunggu persetujuan</p>
                        @endif
                    </div>
                    <div><p class="text-xs text-slate-500">Alasan peminjaman</p><p class="mt-1 font-medium text-slate-800">{{ $peminjaman->alasan ?: '-' }}</p></div>
                    <div><p class="text-xs text-slate-500">Alat</p><div class="mt-1 space-y-1 font-medium text-slate-800">@foreach($peminjaman->detailPinjams as $detail)<p>{{ $detail->alat->nama_alat ?? 'Alat dihapus' }} <span class="text-slate-400">x{{ $detail->jumlah }}</span></p>@endforeach</div></div>
                </div>
                @if($peminjaman->status === 'diajukan')
                    <div class="flex gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('peminjam.peminjaman.edit', $peminjaman) }}" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">Edit Pengajuan</a>
                        <form action="{{ route('peminjam.peminjaman.destroy', $peminjaman) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">Hapus Pengajuan</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center text-sm text-slate-500">Belum ada riwayat peminjaman.</div>
        @endforelse
    </div>
@endsection