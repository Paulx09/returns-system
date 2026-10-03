<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Devolución</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #1e293b;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #dc2626; /* Tai Loy Red */
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .body-content {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 12px 0 20px 0;
        }
        .badge-received { background-color: #e2e8f0; color: #475569; }
        .badge-under_review { background-color: #fef08a; color: #854d0e; }
        .badge-approved { background-color: #bbf7d0; color: #166534; }
        .badge-rejected { background-color: #fecaca; color: #991b1b; }
        .badge-more_information_requested { background-color: #fed7aa; color: #9a3412; }
        .badge-closed { background-color: #cbd5e1; color: #334155; }

        .details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .details-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-box td {
            padding: 6px 0;
            font-size: 14px;
        }
        .details-box td.label {
            color: #64748b;
            width: 40%;
        }
        .details-box td.value {
            font-weight: 600;
            color: #0f172a;
        }
        .comment-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 14px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #1e40af;
        }
        .footer {
            background-color: #f1f5f9;
            text-align: center;
            padding: 16px;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Devoluciones Tai Loy</h1>
        </div>

        <div class="body-content">
            <div class="greeting">Hola, {{ $customerName }}</div>
            <p>Queremos informarte que el estado de tu solicitud de devolución ha sido actualizado.</p>

            <div>
                <strong>Nuevo Estado:</strong><br>
                <span class="status-badge badge-{{ $ticket->current_status }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="details-box">
                <table>
                    <tr>
                        <td class="label">Código de Seguimiento:</td>
                        <td class="value">{{ $ticket->tracking_code }}</td>
                    </tr>
                    <tr>
                        <td class="label">Número de Pedido:</td>
                        <td class="value">{{ $ticket->order->order_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Fecha de Actualización:</td>
                        <td class="value">{{ now()->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>

            @if(!empty($comment))
                <div class="comment-box">
                    <strong>Mensaje del equipo de soporte:</strong>
                    <p style="margin: 6px 0 0 0;">{{ $comment }}</p>
                </div>
            @endif

            <p>Si tienes alguna duda sobre tu caso, puedes revisar el estado y la línea de tiempo de tu ticket en nuestro portal con tu número de pedido y DNI.</p>

            <div style="text-align: center; margin: 25px 0;">
                <a href="{{ route('returns.start') }}" style="background-color: #05a060; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">
                    Consultar Seguimiento en el Portal
                </a>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Tai Loy. Todos los derechos reservados. Este es un correo automático, por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
