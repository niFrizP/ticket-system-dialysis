<?php
session_start();
require_once 'includes/header.php';
?>

<div class="min-h-screen lg:flex">
    <!-- Lado izquierdo - Hero -->
    <div class="lg:w-1/2 hero-image flex items-center justify-center p-8">
        <div class="max-w-md text-white">
            <!-- Logo -->
            <div class="mb-8">
                <div class="bg-white rounded-full w-32 h-32 flex items-center justify-center mx-auto mb-6">
                    <img src="https://via.placeholder.com/120x120?text=TEQMED" alt="TEQMED Logo" class="w-24 h-24">
                </div>
            </div>

            <!-- Título -->
            <h1 class="text-4xl font-bold mb-4 text-center">
                Ticket de llamado<br>
                <span class="text-teqmed-cyan">TEQMED SpA</span>
            </h1>

            <!-- Subtítulo -->
            <p class="text-xl text-center text-gray-200 mb-8">
                Informe su desperfecto completando el formulario
            </p>

            <!-- Info adicional -->
            <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-lg p-6 mt-8">
                <h3 class="font-semibold mb-2">¿Necesita ayuda?</h3>
                <p class="text-sm text-gray-200">
                    Complete el formulario con la mayor cantidad de detalles posible. 
                    Recibirá un número de ticket para el seguimiento de su solicitud.
                </p>
            </div>
        </div>
    </div>

    <!-- Lado derecho - Formulario -->
    <div class="lg:w-1/2 bg-white flex items-center justify-center p-8">
        <div class="w-full max-w-2xl">
            <!-- Barra de progreso -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700" id="progressText">Página 1 de 2</span>
                    <span class="text-xs text-red-600">* Obligatorio</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progressBar" class="progress-bar bg-teqmed-blue h-2 rounded-full" style="width: 50%"></div>
                </div>
            </div>

            <!-- Formulario -->
            <form id="ticketForm" class="space-y-6">
                
                <!-- SECCIÓN 1: Datos de Contacto -->
                <div class="form-section" id="section1">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Datos de contacto</h2>
                        <p class="text-gray-600">Entréguenos sus datos para contactarnos contigo</p>
                    </div>

                    <!-- Cliente -->
                    <div class="mb-6">
                        <label for="cliente" class="block text-gray-700 font-medium mb-2">
                            1. Cliente <span class="text-red-600">*</span>
                        </label>
                        <input type="text" 
                               id="cliente" 
                               name="cliente" 
                               required
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                               placeholder="Escribe tu respuesta">
                    </div>

                    <!-- Nombre y Apellido -->
                    <div class="mb-6">
                        <label for="nombre_apellido" class="block text-gray-700 font-medium mb-2">
                            2. Nombre y Apellido <span class="text-red-600">*</span>
                        </label>
                        <input type="text" 
                               id="nombre_apellido" 
                               name="nombre_apellido" 
                               required
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                               placeholder="Escribe tu respuesta">
                    </div>

                    <!-- Teléfono -->
                    <div class="mb-6">
                        <label for="telefono" class="block text-gray-700 font-medium mb-2">
                            3. Teléfono de contacto <span class="text-red-600">*</span>
                        </label>
                        <input type="tel" 
                               id="telefono" 
                               name="telefono" 
                               required
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                               placeholder="El valor debe ser un número.">
                        <p class="text-sm text-gray-500 mt-1">Formato: +56912345678 o 912345678</p>
                    </div>

                    <!-- Cargo -->
                    <div class="mb-6">
                        <label for="cargo" class="block text-gray-700 font-medium mb-2">
                            4. Cargo <span class="text-red-600">*</span>
                        </label>
                        <input type="text" 
                               id="cargo" 
                               name="cargo" 
                               required
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                               placeholder="Escribe tu respuesta">
                    </div>

                    <!-- Email (opcional) -->
                    <div class="mb-6">
                        <label for="email" class="block text-gray-700 font-medium mb-2">
                            5. Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                               placeholder="correo@ejemplo.com">
                    </div>

                    <!-- Botón siguiente -->
                    <div class="flex justify-end pt-6">
                        <button type="button" 
                                id="btnNext"
                                class="btn-primary text-white px-8 py-3 rounded-lg font-medium">
                            Siguiente
                        </button>
                    </div>
                </div>

                <!-- SECCIÓN 2: Datos de la Falla -->
                <div class="form-section hidden" id="section2">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Datos de la Falla</h2>
                        <p class="text-gray-600">Detállenos cual es el desperfecto que se presentó</p>
                    </div>

                    <!-- ID/Número de equipo -->
                    <div class="mb-6">
                        <label for="id_numero_equipo" class="block text-gray-700 font-medium mb-2">
                            5. ID / Número de equipo
                        </label>
                        <input type="text" 
                               id="id_numero_equipo" 
                               name="id_numero_equipo"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                               placeholder="El valor debe ser un número.">
                    </div>

                    <!-- Modelo de máquina -->
                    <div class="mb-6">
                        <label for="modelo_maquina" class="block text-gray-700 font-medium mb-2">
                            6. Modelo de maquina
                        </label>
                        <input type="text" 
                               id="modelo_maquina" 
                               name="modelo_maquina"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                               placeholder="Escribe tu respuesta">
                    </div>

                    <!-- Falla presentada -->
                    <div class="mb-6">
                        <label for="falla_presentada" class="block text-gray-700 font-medium mb-2">
                            7. Falla presentada <span class="text-red-600">*</span>
                        </label>
                        <textarea id="falla_presentada" 
                                  name="falla_presentada" 
                                  required
                                  rows="4"
                                  class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none resize-none"
                                  placeholder="Escribe tu respuesta"></textarea>
                    </div>

                    <!-- Momento en que se presentó la falla -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-3">
                            8. Momento en que se presentó la falla <span class="text-red-600">*</span>
                        </label>
                        <div class="space-y-3">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="radio" 
                                       name="momento_falla" 
                                       value="En preparación" 
                                       required
                                       class="custom-radio">
                                <span class="text-gray-700">En preparación</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="radio" 
                                       name="momento_falla" 
                                       value="En diálisis" 
                                       required
                                       class="custom-radio">
                                <span class="text-gray-700">En diálisis</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="radio" 
                                       name="momento_falla" 
                                       value="En desinfección" 
                                       required
                                       class="custom-radio">
                                <span class="text-gray-700">En desinfección</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="radio" 
                                       name="momento_falla" 
                                       value="Otras" 
                                       id="momento_otras"
                                       required
                                       class="custom-radio">
                                <span class="text-gray-700">Otras</span>
                            </label>
                            
                            <!-- Campo de texto para "Otras" -->
                            <div id="momento_otras_input" class="hidden ml-8 mt-2">
                                <input type="text" 
                                       name="momento_falla_otras"
                                       class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none"
                                       placeholder="Especifique...">
                            </div>
                        </div>
                    </div>

                    <!-- Acciones posteriores realizadas -->
                    <div class="mb-6">
                        <label for="acciones_realizadas" class="block text-gray-700 font-medium mb-2">
                            9. Acciones posteriores realizadas por el personal
                        </label>
                        <textarea id="acciones_realizadas" 
                                  name="acciones_realizadas"
                                  rows="4"
                                  class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teqmed-blue focus:border-transparent outline-none resize-none"
                                  placeholder="Escribe tu respuesta"></textarea>
                    </div>

                    <!-- Botones de navegación -->
                    <div class="flex justify-between pt-6">
                        <button type="button" 
                                id="btnPrev"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-8 py-3 rounded-lg font-medium transition">
                            Atrás
                        </button>
                        <button type="submit" 
                                id="btnSubmit"
                                class="btn-primary text-white px-8 py-3 rounded-lg font-medium">
                            Enviar
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>