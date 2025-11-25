<?php
// Plantilla HTML para notificar al equipo de soporte
// Variables esperadas antes de include:
// - $ticket (array): datos del ticket
// - $ticketUrl (string): url pública del ticket
// - $qrCodeUrl (string|null): url pública del qr
// - $createdAt (string): fecha formateada
// - $logoCid (string) opcional: cid para imagen embebida
// - $logoUrl (string) opcional: url pública del logo (fallback)
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Nuevo Ticket: <?= htmlspecialchars($ticket['numero_ticket'] ?? '') ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        :root {
            --teqmed-blue: #003d5c;
            --teqmed-cyan: #00bcd4;
            --muted: #6b7280
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #111
        }

        .wrapper {
            max-width: 680px;
            margin: 20px auto;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .08)
        }

        .header {
            background: linear-gradient(135deg, var(--teqmed-blue) 0%, var(--teqmed-cyan) 100%);
            color: #fff;
            padding: 26px 24px;
            text-align: center
        }

        .header .title {
            font-size: 20px;
            margin: 0 0 6px
        }

        .ticket-num {
            display: inline-block;
            background: rgba(255, 255, 255, .12);
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            margin-top: 8px
        }

        .content {
            padding: 22px
        }

        .section {
            background: #f9fafb;
            border-left: 4px solid var(--teqmed-blue);
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 18px
        }

        .label {
            font-weight: 700;
            color: var(--teqmed-blue);
            min-width: 140px;
            display: inline-block
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px solid #eef2f7
        }

        .info-row:last-child {
            border-bottom: none
        }

        .falla-box {
            background: #fff;
            border: 1px solid #e6eef4;
            padding: 12px;
            border-radius: 8px;
            white-space: pre-wrap
        }

        .action {
            display: block;
            text-align: center;
            margin: 18px 0
        }

        .btn {
            display: inline-block;
            background: var(--teqmed-blue);
            color: #fff;
            padding: 12px 26px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700
        }

        .footer {
            background: #f9fafb;
            padding: 18px 20px;
            text-align: center;
            color: var(--muted);
            font-size: 13px
        }

        .qr-wrap {
            display: inline-block;
            background: #fff;
            padding: 12px;
            border-radius: 8px
        }

        @media (max-width:600px) {
            .wrapper {
                margin: 12px
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <?php if (!empty($logoCid) || !empty($logoUrl)): ?>
                <?php if (!empty($logoCid)): ?>
                    <img src="cid:<?= htmlspecialchars($logoCid) ?>" alt="logo" style="height:42px;vertical-align:middle;margin-bottom:8px">
                <?php else: ?>
                    <img src="<?= htmlspecialchars($logoUrl) ?>" alt="logo" style="height:42px;vertical-align:middle;margin-bottom:8px">
                <?php endif; ?>
            <?php endif; ?>
            <div class="title">🔧 Nuevo Ticket de Soporte</div>
            <div class="ticket-num"><?= htmlspecialchars($ticket['numero_ticket'] ?? '') ?></div>
        </div>

        <div class="content">
            <div style="text-align:center;margin-bottom:10px;color:#374151;font-weight:600">
                📅 Recibido el <?= htmlspecialchars($createdAt ?? date('d/m/Y H:i')) ?>
            </div>

            <div class="section">
                <h4 class="section-title" style="margin:0 0 10px">👤 Datos del Cliente</h4>
                <div class="info-row"><span class="label">Cliente:</span><span class="value"><?= htmlspecialchars($ticket['cliente'] ?? '') ?></span></div>
                <div class="info-row"><span class="label">Solicitante:</span><span class="value"><?= htmlspecialchars($ticket['nombre_apellido'] ?? '') ?></span></div>
                <div class="info-row"><span class="label">Cargo:</span><span class="value"><?= htmlspecialchars($ticket['cargo'] ?? '') ?></span></div>
                <div class="info-row"><span class="label">Teléfono:</span><span class="value"><?= htmlspecialchars($ticket['telefono'] ?? '') ?></span></div>
                <?php if (!empty($ticket['email'])): ?>
                    <div class="info-row"><span class="label">Email:</span><span class="value"><?= htmlspecialchars($ticket['email']) ?></span></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($ticket['modelo_maquina']) || !empty($ticket['id_numero_equipo'])): ?>
                <div class="section">
                    <h4 class="section-title" style="margin:0 0 10px">🏥 Datos del Equipo</h4>
                    <?php if (!empty($ticket['id_numero_equipo'])): ?><div class="info-row"><span class="label">ID/Nº Equipo:</span><span class="value"><?= htmlspecialchars($ticket['id_numero_equipo']) ?></span></div><?php endif; ?>
                    <?php if (!empty($ticket['modelo_maquina'])): ?><div class="info-row"><span class="label">Modelo:</span><span class="value"><?= htmlspecialchars($ticket['modelo_maquina']) ?></span></div><?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <h4 class="section-title" style="margin:0 0 10px">⚠️ Descripción de la Falla</h4>
                <div class="falla-box"><?= nl2br(htmlspecialchars($ticket['falla_presentada'] ?? '')) ?></div>
                <div style="margin-top:12px" class="info-row"><span class="label">Momento:</span><span class="value"><strong><?= htmlspecialchars($ticket['momento_falla'] ?? '') ?></strong></span></div>
            </div>

            <div style="text-align:center" class="action">
                <a class="btn" href="<?= htmlspecialchars($ticketUrl) ?>">Ver Ticket Completo →</a>
            </div>

            <?php if (!empty($qrCodeUrl)): ?>
                <div style="text-align:center;margin-top:8px">
                    <div class="qr-wrap">
                        <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR" style="width:160px;height:160px;display:block">
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            Sistema de Tickets TEQMED SpA — Este mensaje es automático. No responder.
        </div>
    </div>
</body>

</html>