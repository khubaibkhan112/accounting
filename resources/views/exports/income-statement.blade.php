<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income Statement Report</title>
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
        .net-income {
            font-weight: bold;
            font-size: 14px;
            margin-top: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 2px solid #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Income Statement (Profit & Loss) Report</h1>
        <p>Date Range: {{ $date_from ?? 'N/A' }} to {{ $date_to ?? 'N/A' }}</p>
    </div>

    <div class="section">
        <div class="section-title">REVENUE</div>
        <table>
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revenue['accounts'] as $entry)
                <tr>
                    <td>{{ $entry['account_code'] }}</td>
                    <td>{{ $entry['account_name'] }}</td>
                    <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>Total Revenue</strong></td>
                    <td class="text-right"><strong>{{ number_format($revenue['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">EXPENSES</div>
        <table>
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses['accounts'] as $entry)
                <tr>
                    <td>{{ $entry['account_code'] }}</td>
                    <td>{{ $entry['account_name'] }}</td>
                    <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>Total Expenses</strong></td>
                    <td class="text-right"><strong>{{ number_format($expenses['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="net-income">
        <strong>Net Income: {{ number_format($net_income, 2) }}</strong>
        @if($is_profit)
            <span style="color: green;">(Profit)</span>
        @else
            <span style="color: red;">(Loss)</span>
        @endif
    </div>
</body>
</html>
