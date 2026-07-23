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
        .summary { margin-top: 14px; }
        .summary td { border: none; padding: 2px 6px; }
    </style>
</head>
<body>
    <div style="margin-bottom: 12px;">
        <strong style="font-size: 14px;">{{ $company['business_name'] }}</strong>
        @if($company['rfc'])
            <span style="color:#555;"> · Tax ID {{ $company['rfc'] }}</span>
        @endif
    </div>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Periodo: {{ $report['period']['from'] }} — {{ $report['period']['to'] }} ·
        Generado: {{ $generatedAt }} · Moneda: {{ $company['currency'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Estado</th>
                <th>Usuario</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['folio'] }}</td>
                    <td>{{ $row['purchased_at'] }}</td>
                    <td>{{ $row['supplier'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['user'] }}</td>
                    <td class="num">${{ number_format($row['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sin compras en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td><strong>Órdenes:</strong> {{ $report['summary']['count'] }}</td></tr>
        <tr><td><strong>Total:</strong> ${{ number_format($report['summary']['total'], 2) }} {{ $company['currency'] }}</td></tr>
    </table>
</body>
</html>
