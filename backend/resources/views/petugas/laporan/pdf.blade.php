<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
            margin: 24px;
            font-size: 11px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            letter-spacing: 0.8px;
            font-weight: bold;
        }

        .meta {
            margin: 10px 0 20px 0;
            font-size: 11px;
            line-height: 1.6;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 0;
        }

        .label {
            width: 120px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 7px 6px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        thead th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        tbody td {
            font-size: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: bold;
            font-size: 9px;
            background: #e5e7eb;
            color: #374151;
        }

        .status-diajukan {
            background: #fef3c7;
            color: #92400e;
        }

        .status-dipinjam {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-dikembalikan {
            background: #d1fae5;
            color: #065f46;
        }

        .status-telat {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            margin-top: 18px;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            font-size: 9px;
            color: #4b5563;
            display: flex;
            justify-content: space-between;
        }

        .page-number {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PEMINJAMAN</h1>
    </div>

    <div class="meta">
        <table class="meta-table">
            <tr>
                <td class="label">Nama Sistem</td>
                <td>: Sistem Peminjaman Barang</td>
            </tr>
            <tr>
                <td class="label">Tanggal Cetak</td>
                <td>: {{ $printedAt }}</td>
            </tr>
            <tr>
                <td class="label">Periode</td>
                <td>: {{ $periode }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 26%;">Peminjam</th>
                <th style="width: 18%;">Status</th>
                <th style="width: 25%;">Tanggal Pinjam</th>
                <th style="width: 25%;">Rencana Kembali</th>
            </tr>
        </thead>
        <tbody>
            @forelse($peminjamans as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->user->name ?? 'User Dihapus' }}</td>
                    <td>
                        <span class="status-badge @if($item->status == 'diajukan') status-diajukan @elseif($item->status == 'dipinjam') status-dipinjam @elseif($item->status == 'dikembalikan') status-dikembalikan @elseif($item->status == 'telat') status-telat @endif">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td>{{ $item->tanggal_pinjam ? \Carbon\Carbon::parse($item->tanggal_pinjam)->format('d-m-Y H:i:s') : '-' }}</td>
                    <td>{{ $item->tanggal_kembali_plan ? \Carbon\Carbon::parse($item->tanggal_kembali_plan)->format('d-m-Y H:i:s') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 18px;">Tidak ada data laporan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>Dicetak pada: {{ $printedAtDateTime }}</div>
        <div class="page-number">Halaman</div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('helvetica', 'normal');
            $pdf->page_text(30, 770, 'Dicetak pada: {{ $printedAtDateTime }}', $font, 8, [0,0,0]);
            $pdf->page_text(540, 770, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 8, [0,0,0]);
        }
    </script>
</body>
</html>
