@extends('layouts.app')

@section('title', 'Log Aktivitas - Panel Admin')
@section('header-title', 'Riwayat Log Aktivitas')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-5 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-bold text-gray-800">Semua Aktivitas</h2>
        <p class="mt-1 text-sm text-gray-600">Riwayat aksi pengguna yang tercatat oleh sistem.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                    <th class="py-3 px-4 border-b whitespace-nowrap">Waktu</th>
                    <th class="py-3 px-4 border-b">Pelaku</th>
                    <th class="py-3 px-4 border-b">Aktivitas</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 border-b whitespace-nowrap">
                            {{ $log->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="py-3 px-4 border-b font-medium text-gray-900">
                            {{ $log->user->name ?? 'Sistem' }}
                        </td>
                        <td class="py-3 px-4 border-b min-w-80">
                            {{ $log->aktivitas }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-8 px-4 text-center text-gray-500">
                            Belum ada log aktivitas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection