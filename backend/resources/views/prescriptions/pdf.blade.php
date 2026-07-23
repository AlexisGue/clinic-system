<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Receta {{ $prescription->folio }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <strong style="font-size: 14px;">{{ $company['business_name'] }}</strong>
    @if(!empty($company['address']))
        <div style="color:#555; font-size: 10px;">{{ $company['address'] }}</div>
    @endif
    @if(!empty($company['phone']))
        <div style="color:#555; font-size: 10px;">Tel: {{ $company['phone'] }}</div>
    @endif

    <h1 style="margin-top: 16px;">Receta médica</h1>
    <div class="meta">
        Folio: {{ $prescription->folio }} · Emitida: {{ $prescription->issued_at?->format('d/m/Y H:i') }} · Generado: {{ $generatedAt }}
    </div>

    <p><strong>Paciente:</strong> {{ trim(($prescription->patient->first_name ?? '').' '.($prescription->patient->last_name ?? '')) }}</p>
    <p><strong>Documento:</strong> {{ $prescription->patient->document_number ?? '—' }}</p>
    <p><strong>Médico:</strong> {{ $prescription->doctor?->user?->name ?? '—' }}</p>

    <table>
        <thead>
            <tr>
                <th>Medicamento</th>
                <th>Dosis</th>
                <th>Frecuencia</th>
                <th>Duración</th>
                <th>Indicaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prescription->items as $item)
                <tr>
                    <td>{{ $item->medicine_name }}</td>
                    <td>{{ $item->dosage }}</td>
                    <td>{{ $item->frequency }}</td>
                    <td>{{ $item->duration }}</td>
                    <td>{{ $item->instructions }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($prescription->notes)
        <p style="margin-top: 14px;"><strong>Notas:</strong> {{ $prescription->notes }}</p>
    @endif

    @if(!empty($company['ticket_footer']))
        <p style="margin-top: 24px; color: #555; font-size: 10px;">{{ $company['ticket_footer'] }}</p>
    @endif
</body>
</html>
