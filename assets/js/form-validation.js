document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ticketForm');
    const sections = document.querySelectorAll('.form-section');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnSubmit = document.getElementById('btnSubmit');
    
    let currentSection = 0;
    const totalSections = sections.length;

    // Configuración de validaciones
    const validations = {
        cliente: { required: true, minLength: 2 },
        nombre_apellido: { required: true, minLength: 3 },
        telefono: { required: true, pattern: /^[0-9+\-\s()]+$/, minLength: 8 },
        cargo: { required: true, minLength: 2 },
        email: { required: false, pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/ },
        id_numero_equipo: { required: false, pattern: /^[a-zA-Z0-9\-]+$/ },
        modelo_maquina: { required: false, minLength: 2 },
        falla_presentada: { required: true, minLength: 10 },
        momento_falla: { required: true },
        acciones_realizadas: { required: false, minLength: 5 }
    };

    // Función para validar campo
    function validateField(field) {
        const name = field.name;
        const value = field.value.trim();
        const validation = validations[name];
        
        if (!validation) return true;

        // Limpiar errores previos
        clearFieldError(field);

        // Required
        if (validation.required && !value) {
            showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        // MinLength
        if (validation.minLength && value.length > 0 && value.length < validation.minLength) {
            showFieldError(field, `Debe tener al menos ${validation.minLength} caracteres`);
            return false;
        }

        // Pattern
        if (validation.pattern && value.length > 0 && !validation.pattern.test(value)) {
            if (name === 'telefono') {
                showFieldError(field, 'Ingrese un teléfono válido');
            } else if (name === 'email') {
                showFieldError(field, 'Ingrese un email válido');
            } else {
                showFieldError(field, 'Formato inválido');
            }
            return false;
        }

        // Si todo está bien
        field.classList.remove('input-error');
        field.classList.add('input-success');
        return true;
    }

    // Mostrar error
    function showFieldError(field, message) {
        field.classList.add('input-error');
        field.classList.remove('input-success');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    // Limpiar error
    function clearFieldError(field) {
        const errorMsg = field.parentNode.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
        field.classList.remove('input-error');
    }

    // Validar sección actual
    function validateCurrentSection() {
        const currentFields = sections[currentSection].querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;

        currentFields.forEach(field => {
            if (field.type === 'radio') {
                const radioGroup = sections[currentSection].querySelectorAll(`input[name="${field.name}"]`);
                const radioChecked = Array.from(radioGroup).some(radio => radio.checked);
                
                if (!radioChecked && field.hasAttribute('required')) {
                    isValid = false;
                    const radioContainer = field.closest('.space-y-2') || field.parentNode;
                    if (!radioContainer.querySelector('.error-message')) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message text-red-600 text-sm mt-1';
                        errorDiv.textContent = 'Debe seleccionar una opción';
                        radioContainer.appendChild(errorDiv);
                    }
                }
            } else {
                if (!validateField(field)) {
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    // Mostrar sección
    function showSection(index) {
        sections.forEach((section, i) => {
            if (i === index) {
                section.classList.remove('hidden');
                section.classList.add('fade-in');
            } else {
                section.classList.add('hidden');
            }
        });

        // Actualizar barra de progreso
        const progress = ((index + 1) / totalSections) * 100;
        progressBar.style.width = progress + '%';
        progressText.textContent = `Página ${index + 1} de ${totalSections}`;

        // Actualizar botones
        btnPrev.classList.toggle('hidden', index === 0);
        btnNext.classList.toggle('hidden', index === totalSections - 1);
        btnSubmit.classList.toggle('hidden', index !== totalSections - 1);

        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Evento siguiente
    btnNext.addEventListener('click', function() {
        if (validateCurrentSection()) {
            currentSection++;
            showSection(currentSection);
        }
    });

    // Evento anterior
    btnPrev.addEventListener('click', function() {
        currentSection--;
        showSection(currentSection);
    });

    // Validación en tiempo real
    const allInputs = form.querySelectorAll('input, textarea, select');
    allInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim()) {
                validateField(this);
            }
        });

        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });

    // Radio "Otras" - mostrar campo de texto
    const radioOtras = document.getElementById('momento_otras');
    const otrasInput = document.getElementById('momento_otras_input');
    
    if (radioOtras && otrasInput) {
        const momentoRadios = document.querySelectorAll('input[name="momento_falla"]');
        momentoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.id === 'momento_otras') {
                    otrasInput.classList.remove('hidden');
                    otrasInput.querySelector('input').required = true;
                } else {
                    otrasInput.classList.add('hidden');
                    otrasInput.querySelector('input').required = false;
                }
            });
        });
    }

    // Submit del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!validateCurrentSection()) {
            return;
        }

        // Mostrar loading
        const submitButton = btnSubmit;
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<div class="spinner mx-auto"></div>';

        try {
            // Ejecutar reCAPTCHA
            const token = await grecaptcha.execute('TU_SITE_KEY_AQUI', {action: 'submit'});
            
            // Agregar token al formulario
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'recaptcha_token';
            tokenInput.value = token;
            form.appendChild(tokenInput);

            // Enviar formulario
            const formData = new FormData(form);
            
            const response = await fetch('process/procesar_ticket.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Mostrar mensaje de éxito
                showSuccessModal(result.ticket_number);
                form.reset();
                currentSection = 0;
                showSection(0);
            } else {
                throw new Error(result.message || 'Error al procesar el ticket');
            }

        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });

    // Modal de éxito
    function showSuccessModal(ticketNumber) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
                <div class="mb-4">
                    <svg class="w-16 h-16 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">¡Ticket Enviado!</h3>
                <p class="text-gray-600 mb-4">Su ticket ha sido registrado exitosamente</p>
                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 mb-1">Número de Ticket:</p>
                    <p class="text-2xl font-bold text-blue-600">${ticketNumber}</p>
                </div>
                <p class="text-sm text-gray-500 mb-6">
                    Recibirá una confirmación por correo electrónico con los detalles de su ticket.
                </p>
                <button onclick="this.closest('.fixed').remove()" 
                        class="btn-primary text-white px-6 py-3 rounded-lg w-full">
                    Cerrar
                </button>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Inicializar
    showSection(0);
});