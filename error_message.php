<?php
$titulo = $titulo ?? "Ocurrió un problema";
$mensaje = $mensaje ?? "No se pudo completar la acción solicitada.";
?>
<div class='max-w-lg mx-auto mt-16 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow p-8 text-center'>
    <div class='flex flex-col items-center mb-4'>
        <svg class='w-12 h-12 text-red-500 mb-2 animate-bounce' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' />
        </svg>
        <h2 class='text-2xl font-bold mb-2'><?php echo $titulo; ?></h2>
    </div>
    <p class='mb-4'><?php echo $mensaje; ?></p>
    <a href='index.html' class='inline-block bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition'>
        ← Volver al inicio
    </a>
</div>