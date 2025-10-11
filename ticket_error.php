<?php
$titulo = $titulo ?? "Ticket no encontrado";
$mensaje = $mensaje ?? "El ticket solicitado no existe o el número es incorrecto.";

// Redirección automática
$redirect_url = "https://llamados.teqmed.cl/";
$segundos = 5;
$radius = 30;
$perimeter = 2 * M_PI * $radius;
?>
<meta http-equiv="refresh" content="<?php echo $segundos; ?>;url=<?php echo $redirect_url; ?>">
<title><?php echo $titulo; ?></title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #003d5c 60%, #00bcd4 100%);
        min-height: 100vh;
    }

    .bg-teqmed-blue {
        background-color: #003d5c !important;
    }

    .bg-teqmed-cyan {
        background-color: #00bcd4 !important;
    }

    .border-teqmed-blue {
        border-color: #003d5c !important;
    }

    .border-teqmed-cyan {
        border-color: #00bcd4 !important;
    }

    .text-teqmed-blue {
        color: #003d5c !important;
    }

    .text-teqmed-cyan {
        color: #00bcd4 !important;
    }
</style>
<div class="max-w-lg mx-auto mt-20 bg-white/90 rounded-xl shadow-lg p-0 text-center border border-teqmed-blue">
    <div class="flex flex-col items-center bg-white rounded-t-xl p-8 pb-2">
        <img src="assets/images/logo.svg" alt="TEQMED Logo" class="h-16 w-16 mb-4 shadow-lg bg-white p-2">
        <svg class="w-12 h-12 text-red-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h2 class="text-2xl font-extrabold mb-1 text-teqmed-blue drop-shadow"><?php echo $titulo; ?></h2>
    </div>
    <div class="bg-white rounded-b-xl px-10 py-8">
        <p class="mb-5 text-gray-700 text-lg"><?php echo $mensaje; ?></p>
        <div class="flex flex-col items-center justify-center mb-4">
            <p class="text-base text-teqmed-blue font-semibold">
                Será redirigido automáticamente en
                <span id="countdown" class="font-bold text-teqmed-cyan"><?php echo $segundos; ?></span> segundos...
            </p>
            <div class="flex justify-center mt-4">
                <!-- Timer Progress Ring SVG -->
                <svg id="progress-ring" width="68" height="68" viewBox="0 0 68 68">
                    <circle
                        cx="34" cy="34" r="30"
                        stroke="#e5e7eb" stroke-width="8"
                        fill="none" />
                    <circle
                        id="timer-circle"
                        cx="34" cy="34" r="30"
                        stroke="#00bcd4"
                        stroke-width="8"
                        fill="none"
                        stroke-linecap="round"
                        stroke-dasharray="<?php echo 2 * M_PI * 30; ?>"
                        stroke-dashoffset="0"
                        style="transition: stroke-dashoffset 0.5s cubic-bezier(.4,0,.2,1);"
                        transform="rotate(-90 34 34)" />
                    <!-- Número dentro del círculo -->
                    <text x="34" y="40" text-anchor="middle" font-size="1.5em" fill="#003d5c" font-family="Inter" font-weight="bold" id="timer-text"><?php echo $segundos; ?></text>
                </svg>
            </div>
        </div>
        <a href="<?php echo $redirect_url; ?>" class="inline-block bg-teqmed-cyan text-white px-7 py-3 rounded-lg font-semibold shadow hover:bg-teqmed-blue transition-all duration-150 mt-4">
            ← Volver al inicio
        </a>
    </div>
    <div class="mt-4 mb-2 flex justify-center">
        <span class="text-xs text-gray-400">TEQMED SpA | Sistema de Tickets</span>
    </div>
</div>
<script>
    // Progress ring (circle fill) y cuenta regresiva sincronizados
    const total = <?php echo $segundos; ?>;
    let seconds = total;
    const countdownElem = document.getElementById('countdown');
    const circle = document.getElementById('timer-circle');
    const text = document.getElementById('timer-text');
    const CIRCLE = 2 * Math.PI * 30;
    let t0 = Date.now();

    function animate() {
        const elapsed = (Date.now() - t0) / 1000;
        let left = Math.max(0, total - elapsed);
        let fraction = left / total;
        let offset = CIRCLE * fraction;
        circle.style.strokeDashoffset = offset;
        if (countdownElem) countdownElem.textContent = Math.ceil(left);
        if (text) text.textContent = Math.ceil(left);
        if (left > 0.08) {
            requestAnimationFrame(animate);
        } else {
            countdownElem.textContent = 0;
            text.textContent = 0;
            circle.style.strokeDashoffset = 0;
        }
    }
    animate();
</script>