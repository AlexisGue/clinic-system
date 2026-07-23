<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <strong style="font-size: 14px;">{{ $company['business_name'] }}</strong>
    <h1>{{ $title }}</h1>
    <div class="meta">
        @if(!empty($report['period']))
            Periodo: {{ $report['period']['from'] }} — {{ $report['period']['to'] }} ·
        @endif
        Generado: {{ $generatedAt }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach(array_keys($report['rows'][0] ?? ['Sin datos' => '']) as $header)
                    <th>{{ str_replace('_', ' ', ucfirst($header)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td>Sin registros en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 12px;"><strong>Total registros:</strong> {{ $report['summary']['count'] ?? 0 }}</p>
    @if(isset($report['summary']['total']))
        <p><strong>Total:</strong> {{ number_format($report['summary']['total'], 2) }} {{ $company['currency'] }}</p>
    @endif
</body>
</html>
