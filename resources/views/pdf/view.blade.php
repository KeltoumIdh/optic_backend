<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <title> TY FAWT VISION</title>
    <style>
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        body {
            position: relative;
            width: 21cm;
            height: 29.7cm;
            margin: 0 auto;
            color: #001028;
            background: #FFFFFF;
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 0;
        }

        .page-container {
            padding: 2cm 1.5cm;
        }

        header {
            padding: 10px 0;
            margin-bottom: 30px;
        }

        /* Logo and title section */
        .header-top {
            margin-bottom: 20px;
        }

        .logo-container {
            float: left;
            width: 60%;
        }

        .company-logo {
            font-size: 40px;
            font-weight: bold;
            color: #FF0000;
            text-transform: uppercase;
        }

        .company-slogan {
            color: #000;
            font-size: 28px;
            font-weight: 500;
            display: inline;
        }

        .invoice-label {
            float: right;
            background-color: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px 20px;
            text-align: right;
            width: 30%;
        }

        .invoice-label h2 {
            margin: 0;
            color: #FF0000;
            font-size: 24px;
            text-transform: uppercase;
        }

        .invoice-label p {
            margin: 5px 0 0 0;
            color: #5D6975;
            font-size: 13px;
        }

        /* Client and company info section */
        .header-info {
            clear: both;
            margin-top: 30px;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 0;
        }

        .client-info {
            float: left;
            width: 48%;
        }

        .company-info {
            float: right;
            width: 48%;
            text-align: right;
        }

        .info-title {
            color: #FF0000;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
        }

        .info-label {
            color: #5D6975;
            font-weight: 600;
            width: 100px;
            display: inline-block;
            font-size: 12px;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        table thead th {
            padding: 10px 8px;
            color: #5D6975;
            background-color: #f8f8f8;
            border-bottom: 2px solid #DDDDDD;
            border-top: 1px solid #DDDDDD;
            white-space: nowrap;
            font-weight: bold;
            text-align: center;
            font-size: 13px;
        }

        table tbody tr:nth-child(odd) td {
            background: #f8f8f8;
        }

        table td {
            padding: 15px 10px;
            text-align: center;
            border-bottom: 1px solid #EEEEEE;
        }

        table td.qty {
            width: 80px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
        }

        table td.ref {
            text-align: left;
            font-size: 13px;
        }

        table td.price {
            width: 100px;
            text-align: right;
            font-size: 13px;
        }

        table td.amount {
            width: 120px;
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }

        table tr.total-row {
            background-color: #f8f8f8;
        }

        table tr.total-row td {
            padding: 12px 10px;
            border-top: 2px solid #DDDDDD;
            font-weight: bold;
            color: #5D6975;
        }

        table tr.grand-total td {
            font-size: 15px;
            border-top: 2px solid #FF0000;
            border-bottom: 2px solid #FF0000;
            font-weight: bold;
            color: #FF0000;
            background-color: #f8f8f8;
        }

        .total-label {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }

        .paid {
            color: #28a745;
        }

        .remaining {
            color: #dc3545;
        }

        footer {
            color: #5D6975;
            width: 100%;
            height: 30px;
            position: absolute;
            bottom: 40px;
            border-top: 1px solid #C1CED9;
            padding: 8px 0;
            text-align: center;
            font-size: 11px;
        }

        .payment-info {
            margin-top: 40px;
            padding: 15px;
            background-color: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 12px;
        }

        .payment-title {
            font-weight: bold;
            color: #5D6975;
            margin-bottom: 10px;
        }

        .terms {
            font-size: 11px;
            color: #777;
            border-top: 1px dashed #e0e0e0;
            padding-top: 10px;
            text-align: center;
        }

        main {
            clear: both;
        }
    </style>
  </head>
  <body>
    <div class="page-container">
        <header class="clearfix">
            <div class="header-top clearfix">
                <div class="logo-container">
                    <div class="company-logo">TY FAWT <span class="company-slogan">vision</span></div>
                </div>
                <div class="invoice-label">
                    <h2>Facture</h2>
                    <p>N° {{ $order->id ?? '#' }}</p>
                    <p>Date: {{ date('d/m/Y', strtotime($order['created_at'])) }}</p>
                </div>
            </div>

            <div class="header-info clearfix">
                <div class="client-info">
                    <div class="info-title">Information Client</div>
                    <div><span class="info-label">Nom:</span> {{ $order->client->name }} {{ $order->client->lname }}</div>
                    <div><span class="info-label">Adresse:</span> {{ $order->client->city }}, {{ $order->client->address }}</div>
                    <div><span class="info-label">Téléphone:</span> {{ $order->client->phone }}</div>
                    <div><span class="info-label">Date:</span> {{ date('d/m/Y H:i', strtotime($order['created_at'])) }}</div>
                </div>
                <div class="company-info">
                    <div class="info-title">Notre Société</div>
                    <div>TY FAWT VISION</div>
                    <div>Derb Sultan,</div>
                    <div>Casablanca, Maroc</div>
                    <div>(212) 6-753 494 47</div>
                </div>
            </div>
        </header>

        <main>
            <table>
                <thead>
                    <tr>
                        <th>QTÉ</th>
                        <th>RÉFÉRENCE</th>
                        <th>PRIX</th>
                        <th>MONTANT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (json_decode($order->cart)->productsCart as $p)
                        @php
                            $product = $products->firstWhere('id', $p->product_id);
                            $totalPrice = ($p->price ?? 0) * ($p->quantity ?? 0);
                        @endphp
                        @if ($product)
                            <tr>
                                <td class="qty">x{{ $p->quantity ?? 0 }}</td>
                                <td class="ref">{{ $product->reference ?? 0 }}</td>
                                <td class="price">{{ number_format($p->price ?? 0, 2) }} dh</td>
                                <td class="amount">{{ number_format($totalPrice, 2) }} dh</td>
                            </tr>
                        @endif
                    @endforeach

                    <tr class="total-row">
                        <td colspan="3" class="total-label">SOUS-TOTAL</td>
                        <td class="amount">{{ number_format($order['total_price'] ?? 0, 2) }} dh</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="total-label">PRIX PAYÉ</td>
                        <td class="amount paid">{{ number_format($order['paid_price'] ?? 0, 2) }} dh</td>
                    </tr>
                    <tr class="grand-total">
                        <td colspan="3" class="total-label">RESTE À PAYER</td>
                        <td class="amount remaining">{{ number_format($order['remain_price'] ?? 0, 2) }} dh</td>
                    </tr>
                </tbody>
            </table>

            <div class="payment-info">
                <div class="payment-title">DÉTAILS DU PAIEMENT</div>
                <div>Méthode de paiement: <strong>{{ ucfirst($order['payment_method'] ?? 'Non spécifié') }}</strong></div>
                @if ($order['is_credit'] == 1)
                    <div>Statut: <strong>Crédit</strong></div>
                    @if ($order['date_fin_credit'])
                        <div>Date de fin de crédit: <strong>{{ date('d/m/Y', strtotime($order['date_fin_credit'])) }}</strong></div>
                    @endif
                @else
                    <div>Statut: <strong>Payé</strong></div>
                @endif
                @if ($order['reference_credit'])
                    <div>Référence: <strong>{{ $order['reference_credit'] }}</strong></div>
                @endif
            </div>

            <div class="terms">
                <p>Cette facture a été générée automatiquement et ne nécessite pas de signature.</p>
                <p>Merci d'avoir choisi TY FAWT VISION. Nous apprécions votre confiance.</p>
            </div>
        </main>

        <footer>
            TY FAWT VISION - SIREN: 123456789 - RC: Casablanca 12345 - Derb Sultan, Casablanca, Maroc - Tél: (212) 6-000 000 00
        </footer>
    </div>
  </body>
</html>
