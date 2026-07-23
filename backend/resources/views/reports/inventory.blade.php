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
        .low { color: #b45309; font-weight: bold; }
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
    <div class="meta">Generado: {{ $generatedAt }} · Moneda: {{ $company['currency'] }}</div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="num">Stock</th>
                <th class="num">Mínimo</th>
                <th>Unidad</th>
                <th class="num">Costo</th>
                <th class="num">Precio</th>
                <th class="num">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['sku'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td class="num {{ $row['is_low_stock'] ? 'low' : '' }}">{{ number_format($row['stock'], 2) }}</td>
                    <td class="num">{{ number_format($row['min_stock'], 2) }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td class="num">${{ number_format($row['cost_price'], 2) }}</td>
                    <td class="num">${{ number_format($row['sale_price'], 2) }}</td>
                    <td class="num">${{ number_format($row['stock_value'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Sin productos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td><strong>Productos:</strong> {{ $report['summary']['count'] }}</td></tr>
        <tr><td><strong>Bajo stock:</strong> {{ $report['summary']['low_stock_count'] }}</td></tr>
        <tr><td><strong>Valor inventario:</strong> ${{ number_format($report['summary']['stock_value'], 2) }} {{ $company['currency'] }}</td></tr>
    </table>
</body>
</html>
