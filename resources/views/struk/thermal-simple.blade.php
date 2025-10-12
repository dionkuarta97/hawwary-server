<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $transaksi->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 44mm auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            width: 44mm;
            padding: 2mm;
            margin: 0;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 1px 0;
            vertical-align: top;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        h1 {
            font-size: 14px;
            margin: 2px 0;
        }

        h2 {
            font-size: 12px;
            margin: 2px 0;
        }

        .small {
            font-size: 10px;
        }

        .mt-1 {
            margin-top: 2px;
        }

        .mt-2 {
            margin-top: 4px;
        }

        .mb-1 {
            margin-bottom: 2px;
        }

        .mb-2 {
            margin-bottom: 4px;
        }

        @media print {
            body {
                width: 44mm;
                padding: 1mm;
            }

            .no-print {
                display: none !important;
            }
        }

        .print-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Print</button>

    <!-- Header -->
    <div class="center mb-2">
        <h1 class="bold">HAWWARY DENTALCARE</h1>
        <div class="small">Jn. Negara KM 9 Tj. Pati</div>
        <div class="small">(+62) 813 7188 6284</div>
    </div>

    <div class="line"></div>

    <!-- Info Transaksi -->
    <table class="mb-1">
        <tr>
            <td class="bold">No. Transaksi</td>
            <td class="right">#{{ str_pad($transaksi->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="bold">Tanggal</td>
            <td class="right">{{ \Carbon\Carbon::parse($transaksi->created_at)->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <!-- Info Pasien -->
    <table class="mb-1">
        <tr>
            <td class="bold">Pasien</td>
            <td class="right">{{ $transaksi->pasien->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="bold">No. RM</td>
            <td class="right">{{ $transaksi->pasien->no_rm ?? '-' }}</td>
        </tr>
        @if($transaksi->pasien && $transaksi->pasien->no_hp)
        <tr>
            <td class="bold">Telp</td>
            <td class="right">{{ $transaksi->pasien->no_hp }}</td>
        </tr>
        @endif
    </table>

    <div class="line"></div>

    <!-- Info Dokter -->
    <table class="mb-1">
        <tr>
            <td class="bold">Dokter</td>
            <td class="right">{{ $transaksi->docter->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="bold">Dental</td>
            <td class="right">{{ $transaksi->dantel->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <!-- Description -->
    @if($transaksi->description)
    <div class="mb-2">
        <div class="bold mb-1">DESCRIPTION:</div>
        <div>{{ $transaksi->description }}</div>
    </div>
    @endif

    <div class="line"></div>

    <!-- Total -->
    <table class="mt-2">
        <tr>
            <td class="bold" style="font-size: 14px;">TOTAL:</td>
            <td class="right bold" style="font-size: 14px;">Rp {{ number_format($transaksi->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line mt-2"></div>

    <!-- Footer -->
    <div class="center mt-2">
        <div class="small">Terima kasih</div>
        <div class="small">Semoga lekas sembuh</div>
        <div class="small mt-1">{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>