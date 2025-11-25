<?php
// Plantilla de email HTML para el cliente (nuevo_ticket_cliente.php)
// Variables esperadas antes de incluir este archivo:
// - $ticket: array asociativo con campos (numero_ticket, cliente, nombre_apellido, email, id_numero_equipo, modelo_maquina, falla_presentada, estado, fecha_visita, created_at)
// - $createdAt: string con la fecha ya formateada (ej. date('d/m/Y H:i', strtotime(...)))
// - $logoCid: (opcional) si quieres usar CID embebido, pasa el ID (ej. 'teqmed_logo'). Si está vacío se usará $logoUrl.
// - $logoUrl: (opcional) URL pública del logo si no usas CID embebido.
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Confirmación de ticket: <?= htmlspecialchars($ticket['numero_ticket'] ?? '') ?></title>
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <style>
        :root {
            --teqmed-blue: #003d5c;
            --teqmed-cyan: #00bcd4;
            --teqmed-gray: #e5e7eb;
            --teqmed-bg: #f4f7fa;
            --teqmed-white: #fff;
            --teqmed-black: #111;
        }

        body {
            background: var(--teqmed-bg);
            color: var(--teqmed-black);
            font-family: 'Segoe UI', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
        }

        .container {
            max-width: 520px;
            margin: 32px auto;
            background: var(--teqmed-white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(90deg, var(--teqmed-blue) 0%, var(--teqmed-cyan) 100%);
            padding: 18px 28px;
            color: var(--teqmed-white);
            border-radius: 12px 12px 0 0;
            text-align: left;
        }

        .logo {
            height: 40px;
            margin-bottom: 8px;
            display: inline-block;
            vertical-align: middle;
        }

        .title {
            font-size: 1.2em;
            font-weight: 700;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .emoji {
            font-size: 1.05em;
        }

        .ticket-number {
            display: inline-block;
            background: var(--teqmed-cyan);
            color: #fff;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 1em;
            letter-spacing: 1px;
            margin-top: 8px;
            font-weight: 700;
        }

        .main {
            padding: 24px;
            background: var(--teqmed-white);
        }

        .info-label {
            color: var(--teqmed-blue);
            font-weight: 700;
            min-width: 120px;
            display: inline-block;
        }

        .info-row {
            margin-bottom: 10px;
            font-size: 0.98em;
        }

        .btn {
            display: inline-block;
            background: var(--teqmed-blue);
            color: #fff !important;
            padding: 10px 26px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            margin-top: 16px;
            font-size: 1em;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
            transition: background .2s;
        }

        .btn:hover {
            background: #00516e;
        }

        .footer {
            background: var(--teqmed-gray);
            color: #333;
            padding: 14px 22px;
            text-align: center;
            font-size: 13px;
            border-radius: 0 0 12px 12px;
        }

        .small-note {
            color: #666;
            font-size: 12px;
            margin-top: 14px;
            opacity: .95;
        }

        @media (max-width:600px) {

            .container,
            .main,
            .header,
            .footer {
                padding: 10px !important
            }

            .main {
                padding-top: 14px !important
            }
        }

        @media (prefers-color-scheme:dark) {
            body {
                background: #181a1b !important;
                color: #fff !important
            }

            .container {
                background: #25282c !important;
                color: #fff !important;
                box-shadow: 0 2px 12px rgba(0, 0, 0, .25) !important
            }

            .header {
                background: linear-gradient(90deg, #003d5c 0%, #00bcd4 100%) !important;
                color: #fff !important
            }

            .main {
                background: #25282c !important;
                color: #fff !important
            }

            .btn {
                background: #00bcd4 !important;
                color: #fff !important
            }

            .info-label {
                color: #00bcd4 !important
            }

            .footer {
                background: #23262a !important;
                color: #bbb !important
            }

            .ticket-number {
                background: #00bcd4 !important;
                color: #fff !important
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <?php if (!empty($logoCid)): ?>
                <!-- Uso de CID embebido (PHPMailer: addEmbeddedImage($path, $logoCid)) -->
                <img src="cid:<?= htmlspecialchars($logoCid) ?>" alt="TEQMED Logo" class="logo">
            <?php elseif (!empty($logoUrl)): ?>
                <!-- Fallback a URL pública -->
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="TEQMED Logo" class="logo">
            <?php endif; ?>

            <div class="title"><span class="emoji">✅</span> Confirmación de recepción de ticket</div>

            <div class="ticket-number">Ticket: <?= htmlspecialchars($ticket['numero_ticket'] ?? '') ?></div>
            <div style="font-size:13px;opacity:.92;margin-top:8px;">
                Fecha: <b><?= htmlspecialchars($createdAt ?? date('d/m/Y H:i')) ?></b>
            </div>
        </div>

        <div class="main">
            <p style="margin-top:0;">Hola <b><?= htmlspecialchars($ticket['nombre_apellido'] ?? $ticket['cliente'] ?? 'cliente') ?></b>,</p>

            <p style="margin-bottom:16px;">
                Hemos recibido tu solicitud y hemos creado el ticket con número
                <strong><?= htmlspecialchars($ticket['numero_ticket'] ?? '') ?></strong>.
                Nuestro equipo revisará la información y te contactaremos a la brevedad.
            </p>

            <div class="info-row"><span class="info-label">Cliente:</span> <?= htmlspecialchars($ticket['cliente'] ?? '') ?></div>
            <div class="info-row"><span class="info-label">Solicitante:</span> <?= htmlspecialchars($ticket['nombre_apellido'] ?? '') ?></div>
            <?php if (!empty($ticket['email'])): ?>
                <div class="info-row"><span class="info-label">Email:</span> <?= htmlspecialchars($ticket['email']) ?></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">Equipo:</span> <?= htmlspecialchars($ticket['modelo_maquina'] ?? 'Sin especificar') ?></div>
            <?php if (!empty($ticket['id_numero_equipo'])): ?>
                <div class="info-row"><span class="info-label">ID Equipo:</span> <?= htmlspecialchars($ticket['id_numero_equipo']) ?></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">Falla reportada:</span> <?= nl2br(htmlspecialchars($ticket['falla_presentada'] ?? '')) ?></div>
            <div class="info-row"><span class="info-label">Estado:</span> <?= htmlspecialchars(ucfirst($ticket['estado'] ?? 'pendiente')) ?></div>

            <?php if (!empty($ticket['fecha_visita'])): ?>
                <div class="info-row"><span class="info-label">Visita agendada:</span>
                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['fecha_visita']))) ?> hrs
                </div>
            <?php endif; ?>

            <div style="margin-top:18px;">
                <a class="btn" href="https://llamados.teqmed.cl/<?= rawurlencode($ticket['numero_ticket'] ?? '') ?>">Ver y seguir mi ticket</a>
            </div>

            <div class="small-note">
                Si necesitas asistencia inmediata, contáctanos al <b>(41) 213 7355</b> o escribe a <b>llamados@teqmed.cl</b>.<br>
                Por favor, guarda este número de ticket para seguimiento.
            </div>

            <div class="small-note" style="margin-top:12px;">
                Este correo es informativo. <b>Por favor no responder a este mensaje</b>.
            </div>
        </div>

        <div class="footer">
            &copy; <?= date('Y') ?> TEQMED SpA — Todos los derechos reservados.
        </div>
    </div>
</body>

</html>