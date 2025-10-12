<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #{{ $transaksi->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 44mm auto;
            margin: 0mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7px;
            line-height: 1.2;
            color: #000;
            background: #fff;
            width: 42mm;
            max-width: 42mm;
            margin: 0 auto;
            padding: 1mm;
        }

        .struk-container {
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 4px;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }

        .header h1 {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .header p {
            font-size: 6px;
            margin: 0.5px 0;
        }

        .info-section {
            margin-bottom: 4px;
            font-size: 6px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
        }

        .info-label {
            font-weight: bold;
            width: 50%;
            font-size: 6px;
        }

        .info-value {
            width: 50%;
            text-align: right;
            font-size: 6px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }

        .items-section {
            margin: 4px 0;
        }

        .items-section strong {
            font-size: 7px;
            display: block;
            margin-bottom: 2px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            font-size: 6px;
        }

        .item-name {
            flex: 1;
            word-wrap: break-word;
        }

        .item-price {
            text-align: right;
            font-weight: bold;
            margin-left: 3px;
        }

        .total-section {
            margin-top: 4px;
            border-top: 1px solid #000;
            padding-top: 3px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
            font-size: 7px;
        }

        .total-row.grand-total {
            font-size: 8px;
            font-weight: bold;
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px solid #000;
        }

        .footer {
            text-align: center;
            margin-top: 6px;
            border-top: 1px dashed #000;
            padding-top: 4px;
            font-size: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 5px;
        }

        .status-sukses {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-gagal {
            background: #f8d7da;
            color: #721c24;
        }

        @media print {
            body {
                width: 42mm;
                max-width: 42mm;
                padding: 1mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: 44mm auto;
                margin: 0mm;
            }
        }

        .print-button {
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
        }

        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>

    <div class="struk-container">
        <!-- Header -->
        <div class="header">
            <h1>HAWWARY DENTALCARE</h1>
            <p>Jn. Negara KM 9 Tj. Pati</p>
            <p>(+62) 813 7188 6284</p>
        </div>

        <!-- Info Transaksi -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">No. Transaksi</span>
                <span class="info-value">#{{ str_pad($transaksi->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($transaksi->created_at)->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Info Pasien -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Pasien</span>
                <span class="info-value">{{ $transaksi->pasien->nama ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">No. RM</span>
                <span class="info-value">{{ $transaksi->pasien->no_rm ?? '-' }}</span>
            </div>
            @if($transaksi->pasien && $transaksi->pasien->no_hp)
            <div class="info-row">
                <span class="info-label">Telp</span>
                <span class="info-value">{{ $transaksi->pasien->no_hp }}</span>
            </div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Info Dokter & Dantel -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Dokter</span>
                <span class="info-value">{{ $transaksi->docter->name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Dental</span>
                <span class="info-value">{{ $transaksi->dantel->name ?? '-' }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Detail Biaya -->
        <div class="items-section">
            <strong>DESCRIPTION:</strong>

            @if($transaksi->description)
            <div class="item-row">
                <span class="item-name">{{ $transaksi->description }}</span>
            </div>
            @endif




        </div>

        <!-- Total -->
        <div class="total-section">

            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>Rp {{ number_format($transaksi->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih</p>
            <p>Semoga lekas sembuh</p>
            <p style="margin-top: 4px; font-size: 5px;">
                {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>