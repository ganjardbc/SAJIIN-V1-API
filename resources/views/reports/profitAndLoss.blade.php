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
      <h1 style="font-size: 16px; text-align: center;">LAPORAN LABA RUGI</h1>
      <p style="text-align: center;">PERIODE: {{ date_format(date_create($response['range_date'][0]), 'd M Y') }} - {{ date_format(date_create($response['range_date'][1]), 'd M Y') }}</p>
      <table style="width: 100%;">
        <tr style="width: 100%;">
          <td style="width: 50%;">
            <h2 style="font-size: 16px; text-align: left;">{{ $response['shop'] ? $response['shop']['name'] : '-' }}</h2>
            Dibuat: {{ $response['user']['name'] }}, Tanggal: {{ date('d M Y') }}
          </td>
          <td style="width: 50%; text-align: right;">
            @if($response['shop'] && $response['shop']['image'])
              <img src="{{ public_path('contents/shops/thumbnails/'.$response['shop']['image']) }}" alt="" style="height: 50px;">
            @endif 
          </td>
        </tr>
      </table>
    </div>

    <div style="width: 100%; height: 22px; margin-bottom: 15px; background-color: #f2f2f2;">
      <h2 style="float: left;">Keterangan</h2>
      <h2 style="float: right; text-align: right">Amount</h2>
    </div>
    
    <div style="width: 100%; margin-bottom: 15px;">
      <h2>Pendapatan</h2>
      <div style="padding-left: 30px; margin-top: 5px;">
        <div style="width: 100%; height: 22px;">
          <p style="float: left;">Penjualan</p>
          <p style="float: right; text-align: right">Rp {{ number_format($response['cash_in'], 0) }}</p>
        </div>
        <div style="width: 100%; height: 22px;">
          <p style="float: left;">Diskon Penjualan</p>
          <p style="float: right; text-align: right; color: red;">(Rp {{ number_format($response['discount_order'], 0) }})</p>
        </div>
      </div>
    </div>

    <div style="width: 100%; height: 22px; margin-bottom: 15px; background-color: #f2f2f2;">
      <h2 style="float: left;">A. Laba(rugi) Kotor</h2>
      <h2 style="float: right; text-align: right">Rp. {{ number_format($response['loss_profit'], 0) }}</h2>
    </div>

    <div style="width: 100%; margin-bottom: 5px;">
      <h2>Beban</h2>
      <div style="padding-left: 30px; margin-top: 5px;">
        <div style="width: 100%; height: 22px;">
          <p style="float: left;">Pengeluaran Outlet</p>
          <p style="float: right; text-align: right">Rp. {{ number_format($response['cash_out'], 0) }}</p>
        </div>
      </div>
    </div>

    <div style="width: 100%; height: 22px; margin-bottom: 15px;">
      <h2 style="float: left;">B. Total Beban</h2>
      <p style="float: right; text-align: right">Rp. {{ number_format($response['cash_out'], 0) }}</p>
    </div>

    <div style="width: 100%; height: 22px; margin-bottom: 15px; background-color: #f2f2f2;">
      <h2 style="float: left;">C. Laba(rugi) Operational (A+B)</h2>
      <h2 style="float: right; text-align: right">Rp. {{ number_format($response['loss_profit_operational'], 0) }}</h2>
    </div>

    <div style="width: 100%; margin-bottom: 5px;">
      <h2>Pendapatan Lain-lain</h2>
      <div style="padding-left: 30px; margin-top: 5px;">
        <div style="width: 100%; height: 22px;">
          <p style="float: left;">Buku Kas</p>
          <p style="float: right; text-align: right">Rp. {{ number_format($response['cash_summary'], 0) }}</p>
        </div>
      </div>
    </div>

    <div style="width: 100%; height: 22px; margin-bottom: 15px;">
      <h2 style="float: left;">D. Total Pendapatan Lain-lain</h2>
      <p style="float: right; text-align: right">Rp. {{ number_format($response['cash_summary'], 0) }}</p>
    </div>

    <div style="width: 100%; height: 22px; margin-bottom: 15px; background-color: #f2f2f2;">
      <h2 style="float: left;">E. Laba(rugi) Bersih (C+D)</h2>
      <h2 style="float: right; text-align: right">Rp. {{ number_format($response['loss_profit_clean'], 0) }}</h2>
    </div>
  </div>
</body>
</html>