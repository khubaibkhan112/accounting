<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance Report</title>
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
    @php($companyName = \App\Models\Setting::get('company_name'))
    <div class="header">
        <h1>Trial Balance Report</h1>
        @if($companyName)
            <p>{{ $companyName }}</p>
        @endif
        <p>Date: {{ $date ?? now()->format('Y-m-d') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Account Type</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trial_balance as $entry)
            <tr>
                <td>{{ $entry['account_code'] }}</td>
                <td>{{ $entry['account_name'] }}</td>
                <td>{{ ucfirst($entry['account_type']) }}</td>
                <td class="text-right">{{ number_format($entry['debit_balance'], 2) }}</td>
                <td class="text-right">{{ number_format($entry['credit_balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3"><strong>TOTALS</strong></td>
                <td class="text-right"><strong>{{ number_format($total_debit, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($total_credit, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
