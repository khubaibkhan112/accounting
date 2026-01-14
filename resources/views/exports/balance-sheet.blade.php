<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Balance Sheet Report</h1>
        <p>Date: {{ $date ?? now()->format('Y-m-d') }}</p>
    </div>

    <div class="section">
        <div class="section-title">ASSETS</div>
        <table>
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assets['accounts'] as $entry)
                <tr>
                    <td>{{ $entry['account_code'] }}</td>
                    <td>{{ $entry['account_name'] }}</td>
                    <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>Total Assets</strong></td>
                    <td class="text-right"><strong>{{ number_format($assets['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">LIABILITIES</div>
        <table>
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($liabilities['accounts'] as $entry)
                <tr>
                    <td>{{ $entry['account_code'] }}</td>
                    <td>{{ $entry['account_name'] }}</td>
                    <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>Total Liabilities</strong></td>
                    <td class="text-right"><strong>{{ number_format($liabilities['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">EQUITY</div>
        <table>
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equity['accounts'] as $entry)
                <tr>
                    <td>{{ $entry['account_code'] }}</td>
                    <td>{{ $entry['account_name'] }}</td>
                    <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="2">Retained Earnings</td>
                    <td class="text-right">{{ number_format($equity['retained_earnings'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2"><strong>Total Equity</strong></td>
                    <td class="text-right"><strong>{{ number_format($equity['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <table>
            <tr class="total-row">
                <td colspan="2"><strong>Total Liabilities and Equity</strong></td>
                <td class="text-right"><strong>{{ number_format($total_liabilities_and_equity, 2) }}</strong></td>
            </tr>
        </table>
    </div>
</body>
</html>
