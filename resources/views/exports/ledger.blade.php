<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ledger Report</title>
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
        .info {
            margin-bottom: 15px;
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
        .summary {
            margin-top: 20px;
        }
        .summary-row {
            padding: 5px 0;
        }
    </style>
</head>
<body>
    @php($companyName = \App\Models\Setting::get('company_name'))
    <div class="header">
        <h1>Ledger Report</h1>
        @if($companyName)
            <p>{{ $companyName }}</p>
        @endif
    </div>
    
    <div class="info">
        <strong>Account:</strong> {{ $account['account_code'] }} - {{ $account['account_name'] }}<br>
        <strong>Date Range:</strong> {{ $date_from ?? 'N/A' }} to {{ $date_to ?? 'N/A' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledger as $entry)
            <tr>
                <td>{{ $entry['date'] }}</td>
                <td>{{ $entry['description'] }}</td>
                <td>{{ $entry['reference_number'] ?? '' }}</td>
                <td class="text-right">{{ number_format($entry['debit_amount'], 2) }}</td>
                <td class="text-right">{{ number_format($entry['credit_amount'], 2) }}</td>
                <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row"><strong>Opening Balance:</strong> {{ number_format($opening_balance, 2) }}</div>
        <div class="summary-row"><strong>Closing Balance:</strong> {{ number_format($closing_balance, 2) }}</div>
        <div class="summary-row"><strong>Total Debit:</strong> {{ number_format($total_debit, 2) }}</div>
        <div class="summary-row"><strong>Total Credit:</strong> {{ number_format($total_credit, 2) }}</div>
    </div>
</body>
</html>
