<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" value="{{ csrf_token() }}"/>
    <title>SAJIIN - CASH REPORT</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
        }
        body {
            padding: 20px;
        }

        #customers {
            border-collapse: collapse;
            width: 100%;
            /* page-break-after: always; */
        }

        #customers td, #customers th {
            border: 1px solid #ddd;
            padding: 5px 8px;
            /* vertical-align: baseline; */
        }

        #customers th {
            font-size: 13px;
        }
        
        #customers td {
            font-size: 13px;
        }

        #customers th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #f2f2f2;
            color: #000;
            font-weight: bold;
        }

        .display-flex {
            display: flex;
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
        <div style="padding-bottom: 15px;">
            <h1 style="font-size: 16px; text-align: center;">Laporan Keuangan</h1>
            <p style="text-align: center;">{{ date('d M Y') }}</p>
            <table style="width: 100%;">
                <tr style="width: 100%;">
                    <td style="width: 50%;">
                        <h2 style="font-size: 22px; text-align: left;">{{ $response['shop']['name'] }}</h2>
                        Periode: {{ date_format(date_create($response['range_date'][0]), 'd M Y') }} - {{ date_format(date_create($response['range_date'][1]), 'd M Y') }}
                    </td>
                    <td style="width: 50%; text-align: right;">
                        @if($response['shop']['image'])
                            <img src="{{ public_path('contents/shops/thumbnails/'.$response['shop']['image']) }}" alt="" style="height: 50px;">
                        @endif 
                    </td>
                </tr>
            </table>
        </div>

        <table id="customers" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="text-align: center;">Modal</th>
                    <th style="text-align: center;">Profit</th>
                    <th style="text-align: center;">Kas Masuk</th>
                    <th style="text-align: center;">Kas Keluar</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 20px 0;">
                        <div style="text-align: center; font-weight: bold; font-size: 13px;">Rp {{ number_format($response['cash_modal'], 0) }}</div>
                    </td>
                    <td style="padding: 20px 0;">
                        <div style="text-align: center; font-weight: bold; font-size: 13px;">Rp {{ number_format($response['cash_profit'], 0) }}</div>
                    </td>
                    <td style="padding: 20px 0;">
                        <div style="text-align: center; font-weight: bold; font-size: 13px;">Rp {{ number_format($response['cash_in'], 0) }}</div>
                    </td>
                    <td style="padding: 20px 0;">
                        <div style="text-align: center; font-weight: bold; font-size: 13px;">Rp {{ number_format($response['cash_out'], 0) }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        <table id="customers" style="margin-bottom: 20px;">
			<thead>
                <tr>
					<th colspan="5" style="text-align: center;">Laporan Penjualan</th>
				</tr>
				<tr>
					<th width="20" style="text-align: center;">No</th>
					<th width="100">Order ID</th>
                    <th width="80">Platform</th>
					<th>Produk</th>
					<th width="110" style="text-align: right;">Total</th>
				</tr>
			</thead>
			<tbody>
                @php $index = 1 @endphp
                @foreach($response['order_list'] as $item)
                    @php $indexDetail = 1 @endphp
                    @php $totalDetails = count($item['details']) @endphp
                    @foreach($item['details'] as $detail)
                        <tr>
                            <td style="text-align: center; border-bottom: {{ $indexDetail != $totalDetails ? '1px solid #fff' : '' }}; }}">
                                @if($indexDetail == 1)
                                    {{ $index++ }}
                                @endif
                            </td>
                            <td style="border-bottom: {{ $indexDetail != $totalDetails ? '1px solid #fff' : '' }}; }}">
                                @if($indexDetail == 1)
                                    <div style="font-weight: bold; font-size: 13px;">{{ $item['order']['order_id'] }}</div>
                                    <div>{{ date_format(date_create($item['order']['created_at']), 'd M Y') }}</div>
                                @endif
                            </td>
                            <td style="border-bottom: {{ $indexDetail != $totalDetails ? '1px solid #fff' : '' }}; }}">
                                @if($indexDetail == 1)
                                    {{ $item['order']['platform_name'] ? $item['order']['platform_name'] : '-' }}
                                @endif
                            </td>
                            <td>
                                <div>
                                    {{ $detail['product_name'] }}
                                    @if($detail['product_detail']) 
                                        <span> - {{ $detail['product_detail'] }}</span>
                                    @endif
                                </div>
                                <div style="padding-bottom: 20px;">
                                    <div style="text-align: right; float: left;">{{ $detail['quantity'] }} x</div>
                                    <div style="text-align: right; float: right; font-weight: bold;">Rp {{ number_format($detail['price'], 0) }}</div>
                                </div>
                            </td>
                            <td style="border-bottom: {{ $indexDetail != $totalDetails ? '1px solid #fff' : '' }}; }}">
                                @if($indexDetail == 1)
                                    <div style="text-align: right; font-weight: bold;">Rp {{ number_format($item['order']['total_price'], 0) }}</div>
                                @endif
                            </td>
                        </tr>
                        @php $indexDetail++ @endphp
                    @endforeach 
                @endforeach

                <tr>
                    <td colspan="3">
                        <div style="text-align: left; font-weight: bold; font-size: 13px;">Total Keseluruhan</div>
                    </td>
                    <td>
                        <div style="text-align: left; font-weight: bold; font-size: 13px;">{{ $response['grand_item'] }} produk</div>
                    </td>
                    <td>
                        <div style="text-align: right; font-weight: bold; font-size: 13px;">Rp {{ number_format($response['grand_total'], 0) }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        <table id="customers" style="margin-bottom: 20px;">
			<thead>
                <tr>
					<th colspan="5" style="text-align: center;">Laporan Pengeluaran</th>
				</tr>
				<tr>
					<th width="20" style="text-align: center;">No</th>
					<th width="100">Expense ID</th>
					<th width="80">Tgl Pengeluaran</th>
                    <th>Tipe Pengeluaran</th>
					<th width="110" style="text-align: right;">Total</th>
				</tr>
			</thead>
			<tbody>
                @php $i = 1 @endphp
                @foreach($response['expense_list'] as $item)
                <tr>
					<td style="text-align: center;">{{ $i++ }}</td>
					<td>{{ $item['expense']['expense_list_id'] }}</td>
                    <td>{{ date_format(date_create($item['expense']['expense_date']), 'd M Y') }}</td>
                    <td>
                        <div>{{ $item['type']['name'] }}</div>
                        @if($item['expense']['description'])
                            <div>NB : {{ $item['expense']['description'] }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="text-align: right; font-weight: bold;">Rp {{ number_format($item['expense']['expense_price'], 0) }}</div>
                    </td>
				</tr>
                @endforeach 

                <!-- <tr>
                    <td colspan="4">
                        <div style="text-align: left; font-weight: normal; font-size: 13px;">Total Pengeluaran</div>
                    </td>
                    <td>
                        <div style="text-align: right; font-weight: normal; font-size: 13px;">Rp {{ number_format($response['expense_list_total'], 0) }}</div>
                    </td>
                </tr> -->

                <!-- <tr>
                    <td colspan="4">
                        <div style="text-align: left; font-weight: normal; font-size: 13px;">Kembalian Penjualan</div>
                    </td>
                    <td>
                        <div style="text-align: right; font-weight: normal; font-size: 13px;">Rp {{ number_format($response['grand_change'], 0) }}</div>
                    </td>
                </tr> -->

                <tr>
                    <td colspan="4">
                        <div style="text-align: left; font-weight: bold; font-size: 13px;">Total Keseluruhan</div>
                    </td>
                    <td>
                        <div style="text-align: right; font-weight: bold; font-size: 13px;">Rp {{ number_format($response['cash_out'], 0) }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>