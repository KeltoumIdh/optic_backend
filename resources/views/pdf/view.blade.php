<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Example 1</title>
    <link rel="stylesheet" href="style.css" media="all" />
    <style>
        .clearfix:after {
        content: "";
        display: table;
        clear: both;
        }

        a {
        color: #5D6975;
        text-decoration: underline;
        }

        body {
        position: relative;
        width: fit-content;
        height: 29.7cm;
        margin: 0 auto;
        color: #001028;
        background: #FFFFFF;
        font-family: Arial, sans-serif;
        font-size: 12px;
        font-family: Arial;
        }

        header {
        padding: 10px 0;
        margin-bottom: 30px;
        }

        #logo {
        text-align: center;
        margin-bottom: 10px;
        padding: 0;
        font-size:35px;
        font-weight:bold;
        color:red;
        }

        #logo img {
        width: 90px;
        }

        h1 {
        border-top: 1px solid  #5D6975;
        border-bottom: 1px solid  #5D6975;
        color: #5D6975;
        font-size: 2.4em;
        line-height: 1.4em;
        font-weight: normal;
        text-align: center;
        margin: 0 0 20px 0;
        background: url(dimension.png);
        }

        #project {
        float: left;
        }

        #project span {
        color: #5D6975;
        text-align: right;
        width: 52px;
        margin-right: 10px;
        display: inline-block;
        font-size: 0.8em;
        }

        #company {
        float: right;
        text-align: right;
        }

        #project div,
        #company div {
        white-space: nowrap;
        }

        table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
        }

        table tr:nth-child(2n-1) td {
        background: #F5F5F5;
        }

        table th,
        table td {
        text-align: center;
        }

        table th {
        padding: 5px 20px;
        color: #5D6975;
        border-bottom: 1px solid #C1CED9;
        white-space: nowrap;
        font-weight: normal;
        }

        table .service,
        table .desc {
        text-align: left;
        }

        table th.aa,
        table td {
        padding: 20px;
        text-align: right;
        }

        table td.service,
        table td.desc {
        vertical-align: top;
        }

        table td.unit,
        table td.qty,
        table td.total {
        font-size: 1.2em;
        }

        table td.grand {
        border-top: 1px solid #5D6975;;
        }

        #notices .notice {
        color: #5D6975;
        font-size: 1.2em;
        }

        footer {
        color: #5D6975;
        width: 100%;
        height: 30px;
        position: absolute;
        bottom: 0;
        border-top: 1px solid #C1CED9;
        padding: 8px 0;
        text-align: center;
}
    </style>
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
        {{-- <img src="/public/assets/logo/logo.png"> --}}
        TY FWT </br><span style="color: black ; font-size: 30px; padding:0">vision</span>
      </div>
      <h1>Bon de livraison</h1>
      <div id="company" class="clearfix">
        <div>TY FWT VISION</div>
        <div>Derb Sultan,<br /> Casablanca, Maroc</div>
        <div>(212) 6-000 000 00</div>
        <!-- <div><a href="mailto:company@example.com">company@example.com</a></div> -->
      </div>
      <div id="project">
        <!-- <div><span>PROJECT</span> Website development</div> -->
        <div><span>CLIENT</span> {{ $order->client->name }}{{ $order->client->lname }}</div>
        <div><span>ADDRESS</span> {{ $order->client->city }},{{ $order->client->address }}</div>
        <div><span>TELEPHONE</span>{{ $order->client->phone }}</div>
        <div><span>DATE</span>{{ $order['created_at']}}</div>
        <!-- <div><span>DUE DATE</span> September 17, 2015</div> -->
      </div>
    </header>
    <main>
      <table>
        <thead>
          <tr>
            <th class="service">QTY</th>
            <th class="desc">REFERENCE</th>
            <th class="aa">PRIX</th>
            <th  class="aa">MONTANT</th>
          </tr>
        </thead>
        <tbody>
            @foreach (json_decode($order->cart)->productsCart as $p)
    @php
        $product = $products->firstWhere('id', $p->product_id);
    @endphp
    @if ($product)
        <tr>
            <td class="service">x{{ $p->quantity ?? 0 }}</td>
            {{-- <td class="service">{{ $loop->iteration ?? 0 }}</td> --}}
            <td class="desc">{{ $product->reference ?? 0 }}</td>
            <td class="unit">{{ $p->price ?? 0 }}</td>
            <td class="total">${{ $p->price ?? 0 * $p->quantity ?? 0 }}</td>
        </tr>
    @endif
@endforeach

<tr>
            <td colspan="3" class="grand total">GRAND TOTAL</td>
            <td class="grand total">${{ $order['total_price'] ?? '#' }}</td>
          </tr>
          <tr>
            <td colspan="3">PRIX PAYÉ</td>
            <td class="total">${{ $order['paid_price'] ?? '#' }}</td>
          </tr>
          <tr>
            <td colspan="3">PRIX RESTANT</td>
            <td class="total">${{ $order['remain_price'] ?? '#' }}</td>
          </tr>

        </tbody>
      </table>
      <!-- <div id="notices">
        <div>NOTICE:</div>
        <div class="notice">A finance charge of 1.5% will be made on unpaid balances after 30 days.</div>
      </div> -->
    </main>
    <!-- <footer>
      Invoice was created on a computer and is valid without the signature and seal.
    </footer> -->
  </body>
</html>
