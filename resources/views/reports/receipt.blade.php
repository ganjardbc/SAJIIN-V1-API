<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" value="{{ csrf_token() }}"/>
    <title>SAJIIN - RECEIPT</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
        }

        .border-top {
            border-top: 1px solid #ddd;
        }

        #container {
            padding: 10px;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
        }
        .table th, .table td {
            width: 50%;
            font-size: 11px;
        }
        .table th.left, .table td.left {
            width: calc(100% - 110px);
        }
        .table th.middle, .table td.middle {
            width: 30px;
            text-align: center;
        }
        .table th.right, .table td.right {
            width: 80px;
            text-align: right;
        }

        .table td, th {
            vertical-align: baseline;
        }

        .table th {
            font-size: 11px;
            padding-bottom: 8px;
        }
        
        .table td {
            font-size: 11px;
        }

        .table th {
            text-align: left;
            color: #000;
            font-weight: bold;
        }

        .space {
            margin: 5px 0;
        }

        .image {
            position: relative;
            width: auto;
            height: 50px;
            margin: auto;
            text-align: center;
        }

        .text-logo-header {
            font-size: 13px; 
            text-align: center;
            font-weight: bold;
        }

        .text-logo-content {
            font-size: 11px; 
            text-align: center;
        }

        .text-align-center {
            text-align: center;
        }

        .qrcode-continer {
            position: relative;
            width: 120px; 
            height: 120px; 
            background-color: #f0f0f0;
            margin: auto;
        }
        .qrcode-continer img {
            width: 100%;
        }

        .display-flex {
            display: flex;
            flex-direction: row;
        }
        .display-flex.column {
            flex-direction: column;
        }
        .display-flex.space-between {
            justify-content: space-between;
        }
        .display-flex.flex-end {
            justify-content: flex-end;
            align-items: flex-end;
        }
    </style>
</head>
<body>
    <div id="container">
        <div class="space">
            <div class="image">
                <img 
                    src="{{ public_path('contents/shops/thumbnails/'.$response['data']['shop']['image']) }}" 
                    alt="" 
                    style="height: 50px;">
            </div>
        </div>

        <div class="space">
            <h1 class="text-logo-header">{{ $response['data']['shop']['name'] }}</h1>
        </div>

        <div class="space">
            <div class="text-logo-content">{{ $response['data']['shop']['location'] }}</div>
            <div class="text-logo-content">{{ $response['data']['shop']['phone'] }}</div>
        </div>

        <div class="space">
            <table>
                <tr>
                    <td width="50">ID Pesanan</td>
                    <td>: {{ $response['data']['order']['order_id'] }}</td>
                </tr>
                <tr>
                    <td width="50">Tanggal</td>
                    <td>: {{ date_format(date_create($response['data']['order']['created_at']), 'd/m/Y') }}</td>
                </tr>
                @if ($response['data']['order']['cashier_name'])
                    <tr>
                        <td width="50">Kasir</td>
                        <td>: {{ $response['data']['order']['cashier_name'] }}</td>
                    </tr>
                @endif 
                @if ($response['data']['order']['customer_name'])
                    <tr>
                        <td width="50">Pelanggan</td>
                        <td>: {{ $response['data']['order']['customer_name'] }}</td>
                    </tr>
                @endif
                @if ($response['data']['order']['table_name'])
                    <tr>
                        <td width="50">Meja</td>
                        <td>: {{ $response['data']['order']['table_name'] }}</td>
                    </tr>
                @endif
                @if ($response['data']['order']['platform_name'])
                    <tr>
                        <td width="50">Platform</td>
                        <td>: {{ $response['data']['order']['platform_name'] }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="space border-top" style="padding-top: 5px;">
            <table class="table">
                <thead>
                    <th class="left">Produk</th>
                    <th class="middle">Qty</th>
                    <th class="right">Total</th>
                </thead>
                <tbody>
                    @foreach($response['data']['details'] as $item)
                        <tr>
                            <td style="padding-bottom: 5px;" class="left">
                                <div>
                                    <span>{{ $item['product_name'] }}</span>
                                    @if($item['product_detail']) 
                                        <span>- {{ $item['product_detail'] }}</span>
                                    @endif
                                </div>
                                <div>Rp. {{ number_format($item['price'], 0) }}</div>
                            </td>
                            <td style="padding-bottom: 5px;" class="middle">{{ $item['quantity'] }}</td>
                            <td style="padding-bottom: 5px;" class="right">Rp. {{ number_format($item['subtotal'], 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space border-top" style="padding-top: 5px;">
            <table class="table">
                <tbody>
                    <tr>
                        <td class="left">Total</td>
                        <td class="middle"></td>
                        <td class="right">Rp. {{ number_format($response['data']['order']['total_price'], 0) }}</td>
                    </tr>
                    <tr>
                        <td class="left">Diskon</td>
                        <td class="middle"></td>
                        <td class="right">Rp. 0</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- HIDDEN TEMPORARY -->
        <!-- @if($response['data']['order']['is_discount'])
            <div class="space border-top" style="padding-top: 5px;">
                <table class="table">
                    <tbody>
                        <tr>
                            <td class="left">Discount</td>
                            <td class="middle"></td>
                            <td class="right">Rp. {{ number_format($response['data']['order']['total_discount'], 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif  -->
        
        <div class="space border-top" style="padding-top: 5px;">
            <table class="table">
                <tbody>
                    <tr>
                        <td class="left">Bayar</td>
                        <td class="middle"></td>
                        <td class="right">Rp. {{ number_format($response['data']['order']['bills_price'], 0) }}</td>
                    </tr>
                    <tr>
                        <td class="left">Kembali</td>
                        <td class="middle"></td>
                        <td class="right">Rp. {{ number_format($response['data']['order']['change_price'], 0) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="space" style="padding-top: 5px;">
            <div class="text-logo-content">Scan Untuk Cek Pesanan</div>
            <div class="qrcode-continer" style="margin-top: 5px;">
                <img src="data:image/png;base64, {{ $response['data']['qrcode'] }}">
            </div>
        </div>
    </div>
</body>
</html>