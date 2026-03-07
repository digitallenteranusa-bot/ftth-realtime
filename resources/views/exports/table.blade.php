<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 4px;
            font-size: 18px;
        }

        .date {
            text-align: center;
            margin-bottom: 16px;
            color: #666;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #999;
            padding: 5px 8px;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #999;
            margin-top: 20px;
        }

        @media print {
            body {
                padding: 0;
            }

            th, td {
                white-space: normal;
                font-size: 10px;
                padding: 3px 5px;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .no-print {
                display: none;
            }
        }

        @page {
            size: landscape;
            margin: 10mm;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="date">Generated on {{ now()->format('d M Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align:center; padding:20px;">No data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Total records: {{ count($rows) }}</p>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
