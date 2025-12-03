document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ticketForm');
    const sections = document.querySelectorAll('.form-section');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnSubmit = document.getElementById('btnSubmit');
    const centroInput = document.getElementById('centro_busqueda');
    const centroHiddenInput = document.getElementById('centro_id');
    const clienteHiddenInput = document.getElementById('cliente_id');
    const centroSugerencias = document.getElementById('centro_sugerencias');
    const equipoInput = document.getElementById('id_numero_equipo');
    const equipoHiddenInput = document.getElementById('equipo_id');
    const equipoSugerencias = document.getElementById('equipo_sugerencias');
    const modeloInput = document.getElementById('modelo_maquina');

    let currentSection = 0;
    const totalSections = sections.length || 1;

    // Leer site key de Turnstile desde data attribute (fallback a null)
    const turnstileKey = form ? form.dataset.turnstileKey || null : null;

    // Variables Turnstile
    let turnstileWidgetId = null;
    let turnstileResolve = null;
    let turnstileTimeout = null;

    // Configuración de validaciones
    const validations = {
        centro_id: { required: true },
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

    function showCentroSugerencias() {
        if (!centroSugerencias || !centroInput) return;
        centroSugerencias.classList.remove('hidden');
        centroInput.setAttribute('aria-expanded', 'true');
    }

    function showEquipoSugerencias() {
        if (!equipoSugerencias || !equipoInput) return;
        equipoSugerencias.classList.remove('hidden');
        equipoInput.setAttribute('aria-expanded', 'true');
    }

    function hideEquipoSugerencias() {
        if (!equipoSugerencias || !equipoInput) return;
        equipoSugerencias.classList.add('hidden');
        equipoInput.setAttribute('aria-expanded', 'false');
    }

    function resetEquipoSelection(clearText = false) {
        if (equipoHiddenInput) equipoHiddenInput.value = '';
        if (clearText && equipoInput) equipoInput.value = '';
        if (modeloInput && clearText) modeloInput.value = '';
        if (equipoSugerencias) equipoSugerencias.innerHTML = '';
        hideEquipoSugerencias();
    }

    function renderEquipoSugerencias(items, query) {
        if (!equipoSugerencias) return;

        if (!items || items.length === 0) {
            equipoSugerencias.innerHTML = `<div class="px-4 py-2 text-sm text-gray-500">No se encontraron equipos para "${query}"</div>`;
            showEquipoSugerencias();
            return;
        }

        equipoSugerencias.innerHTML = items.map(item => {
            const payload = encodeURIComponent(JSON.stringify(item));
            const desc = [item.marca, item.modelo].filter(Boolean).join(' • ');
            return `
                <button type="button" class="w-full text-left px-4 py-2 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none" data-equipo="${payload}">
                    <span class="font-medium text-gray-800">${item.id_maquina || item.codigo || 'Equipo'}</span>
                    ${desc ? `<span class="text-xs text-gray-500 block">${desc}</span>` : ''}
                </button>
            `;
        }).join('');

        showEquipoSugerencias();
    }

    function selectEquipo({ id, id_maquina, codigo, marca, modelo }) {
        if (!equipoInput || !equipoHiddenInput) return;
        const displayValue = id_maquina || codigo || '';
        equipoHiddenInput.value = id || '';
        equipoInput.value = displayValue;
        if (modeloInput) {
            const modeloTexto = [marca, modelo].filter(Boolean).join(' ');
            modeloInput.value = modeloTexto.trim();
        }
        equipoInput.dispatchEvent(new Event('blur'));
        hideEquipoSugerencias();
    }

    function setEquipoLoadingState(message) {
        if (!equipoSugerencias) return;
        equipoSugerencias.innerHTML = `<div class="px-4 py-2 text-sm text-gray-500">${message}</div>`;
        showEquipoSugerencias();
    }

    function setupEquipoAutocomplete() {
        if (!equipoInput || !equipoHiddenInput || !equipoSugerencias) return;

        let debounceTimer = null;
        let abortController = null;

        equipoInput.addEventListener('input', () => {
            const value = equipoInput.value.trim();
            resetEquipoSelection(false);

            if (!centroHiddenInput || !centroHiddenInput.value) {
                setEquipoLoadingState('Seleccione primero un centro médico');
                return;
            }

            if (value.length < 1) {
                hideEquipoSugerencias();
                return;
            }

            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                if (abortController) abortController.abort();
                abortController = new AbortController();

                try {
                    setEquipoLoadingState('Buscando equipos...');
                    const params = new URLSearchParams({
                        q: value,
                        centro_id: centroHiddenInput.value
                    });
                    const response = await fetch(`/process/buscar_equipos.php?${params.toString()}`, {
                        signal: abortController.signal,
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!response.ok) throw new Error('No se pudo completar la búsqueda de equipos');

                    const data = await response.json();
                    if (data.success) {
                        renderEquipoSugerencias(data.results || [], value);
                    } else {
                        setEquipoLoadingState(data.message || 'No hay resultados');
                    }
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    setEquipoLoadingState('Error al buscar equipos');
                }
            }, 300);
        });

        equipoInput.addEventListener('focus', () => {
            if (equipoSugerencias && equipoSugerencias.innerHTML.trim() !== '') {
                showEquipoSugerencias();
            }
        });

        equipoInput.addEventListener('blur', () => {
            setTimeout(() => hideEquipoSugerencias(), 200);
        });

        equipoSugerencias.addEventListener('click', (event) => {
            const target = event.target.closest('button[data-equipo]');
            if (!target) return;
            try {
                const data = JSON.parse(decodeURIComponent(target.getAttribute('data-equipo')));
                selectEquipo(data);
            } catch (err) {
                hideEquipoSugerencias();
            }
        });

        document.addEventListener('click', (event) => {
            if (!equipoInput.contains(event.target) && !equipoSugerencias.contains(event.target)) {
                hideEquipoSugerencias();
            }
        });

        equipoInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideEquipoSugerencias();
                equipoInput.blur();
            }
        });
    }

    function hideCentroSugerencias() {
        if (!centroSugerencias || !centroInput) return;
        centroSugerencias.classList.add('hidden');
        centroInput.setAttribute('aria-expanded', 'false');
    }

    function renderCentroSugerencias(items, query) {
        if (!centroSugerencias) return;

        if (!items || items.length === 0) {
            centroSugerencias.innerHTML = `<div class="px-4 py-2 text-sm text-gray-500">No se encontraron resultados para "${query}"</div>`;
            showCentroSugerencias();
            return;
        }

        centroSugerencias.innerHTML = items.map(item => {
            const payload = encodeURIComponent(JSON.stringify(item));
            const clienteLabel = item.cliente_nombre ? `<span class="text-xs text-gray-500 block">${item.cliente_nombre}</span>` : '';
            return `
                <button type="button" class="w-full text-left px-4 py-2 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none" data-centro="${payload}">
                    <span class="font-medium text-gray-800">${item.nombre}</span>
                    ${clienteLabel}
                </button>
            `;
        }).join('');

        showCentroSugerencias();
    }

    function resetCentroSelection(clearText = false) {
        if (centroHiddenInput) centroHiddenInput.value = '';
        if (clienteHiddenInput) clienteHiddenInput.value = '';
        if (clearText && centroInput) centroInput.value = '';
    }

    function selectCentro({ id, nombre, cliente_id }) {
        if (!centroInput || !centroHiddenInput) return;
        centroHiddenInput.value = id || '';
        centroInput.value = nombre || '';
        if (clienteHiddenInput) clienteHiddenInput.value = cliente_id || '';
        centroInput.dispatchEvent(new Event('blur'));
        hideCentroSugerencias();
        resetEquipoSelection(true);
    }

    function setCentroLoadingState(message) {
        if (!centroSugerencias) return;
        centroSugerencias.innerHTML = `<div class="px-4 py-2 text-sm text-gray-500">${message}</div>`;
        showCentroSugerencias();
    }

    function setupCentroAutocomplete() {
        if (!centroInput || !centroHiddenInput || !centroSugerencias) return;

        let debounceTimer = null;
        let abortController = null;

        centroInput.addEventListener('input', () => {
            const value = centroInput.value.trim();
            resetCentroSelection(false);

            if (value.length < 3) {
                hideCentroSugerencias();
                return;
            }

            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                if (abortController) abortController.abort();
                abortController = new AbortController();

                try {
                    setCentroLoadingState('Buscando centros...');
                    const response = await fetch(`/process/buscar_clientes.php?q=${encodeURIComponent(value)}`, {
                        signal: abortController.signal,
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!response.ok) throw new Error('No se pudo completar la búsqueda');

                    const data = await response.json();
                    if (data.success) {
                        const results = (data.results || []).map(item => ({
                            id: item.id,
                            nombre: item.nombre,
                            cliente_id: item.cliente_id,
                            cliente_nombre: item.cliente_nombre
                        }));
                        renderCentroSugerencias(results, value);
                    } else {
                        setCentroLoadingState(data.message || 'No hay resultados');
                    }
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    setCentroLoadingState('Error al buscar centros');
                }
            }, 300);
        });

        centroInput.addEventListener('focus', () => {
            if (centroSugerencias && centroSugerencias.innerHTML.trim() !== '') {
                showCentroSugerencias();
            }
        });

        centroInput.addEventListener('blur', () => {
            setTimeout(() => hideCentroSugerencias(), 200);
        });

        centroSugerencias.addEventListener('click', (event) => {
            const target = event.target.closest('button[data-centro]');
            if (!target) return;
            try {
                const data = JSON.parse(decodeURIComponent(target.getAttribute('data-centro')));
                selectCentro(data);
            } catch (err) {
                hideCentroSugerencias();
            }
        });

        document.addEventListener('click', (event) => {
            if (!centroInput.contains(event.target) && !centroSugerencias.contains(event.target)) {
                hideCentroSugerencias();
            }
        });

        centroInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideCentroSugerencias();
                centroInput.blur();
            }
        });
    }

    function validateField(field) {
        const name = field.name;
        const value = (field.value || '').trim();
        const validation = validations[name];
        if (!validation) return true;

        clearFieldError(field);

        if (validation.required && !value) {
            showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        if (validation.minLength && value.length > 0 && value.length < validation.minLength) {
            showFieldError(field, `Debe tener al menos ${validation.minLength} caracteres`);
            return false;
        }

        if (validation.pattern && value.length > 0 && !validation.pattern.test(value)) {
            if (name === 'telefono') showFieldError(field, 'Ingrese un teléfono válido');
            else if (name === 'email') showFieldError(field, 'Ingrese un email válido');
            else showFieldError(field, 'Formato inválido');
            return false;
        }

        field.classList.remove('input-error');
        field.classList.add('input-success');
        return true;
    }

    function showFieldError(field, message) {
        field.classList.add('input-error');
        field.classList.remove('input-success');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    function clearFieldError(field) {
        if (!field || !field.parentNode) return;
        const errorMsg = field.parentNode.querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
        field.classList.remove('input-error');
    }

    function validateCurrentSection() {
        const currentFields = sections[currentSection]
            ? sections[currentSection].querySelectorAll('input[required], textarea[required], select[required]')
            : [];
        let isValid = true;

        currentFields.forEach(field => {
            if (field.type === 'radio') {
                const radioGroup = sections[currentSection].querySelectorAll(`input[name="${field.name}"]`);
                const radioChecked = Array.from(radioGroup).some(r => r.checked);
                if (!radioChecked && field.hasAttribute('required')) {
                    isValid = false;
                    const radioContainer = field.closest('.space-y-3') || field.parentNode;
                    if (!radioContainer.querySelector('.error-message')) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message text-red-600 text-sm mt-1';
                        errorDiv.textContent = 'Debe seleccionar una opción';
                        radioContainer.appendChild(errorDiv);
                    }
                }
            } else {
                if (!validateField(field)) isValid = false;
            }
        });

        return isValid;
    }

    function showSection(index) {
        sections.forEach((section, i) => {
            if (i === index) {
                section.classList.remove('hidden');
                section.classList.add('fade-in');
            } else {
                section.classList.add('hidden');
            }
        });

        const progress = ((index + 1) / totalSections) * 100;
        if (progressBar) progressBar.style.width = progress + '%';
        if (progressText) progressText.textContent = `Página ${index + 1} de ${totalSections}`;

        if (btnPrev) btnPrev.classList.toggle('hidden', index === 0);
        if (btnNext) btnNext.classList.toggle('hidden', index === totalSections - 1);
        if (btnSubmit) btnSubmit.classList.toggle('hidden', index !== totalSections - 1);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    if (btnNext) {
        btnNext.addEventListener('click', () => {
            if (validateCurrentSection()) {
                currentSection++;
                showSection(currentSection);
            }
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            currentSection--;
            showSection(currentSection);
        });
    }

    const allInputs = form ? form.querySelectorAll('input, textarea, select') : [];
    allInputs.forEach(input => {
        input.addEventListener('blur', function () {
            if (this.value && this.value.trim()) validateField(this);
        });
        input.addEventListener('input', function () {
            clearFieldError(this);
        });
    });

    const radioOtras = document.getElementById('momento_otras');
    const otrasInput = document.getElementById('momento_otras_input');
    if (radioOtras && otrasInput) {
        const momentoRadios = document.querySelectorAll('input[name="momento_falla"]');
        momentoRadios.forEach(radio => radio.addEventListener('change', function () {
            if (this.id === 'momento_otras') {
                otrasInput.classList.remove('hidden');
                otrasInput.querySelector('input').required = true;
            } else {
                otrasInput.classList.add('hidden');
                otrasInput.querySelector('input').required = false;
            }
        }));
    }

    // --- Turnstile helpers ---
    function renderTurnstileWidget(container) {
        return turnstile.render(container, {
            sitekey: turnstileKey,
            size: 'invisible',
            callback: function (token) {
                if (turnstileResolve) {
                    turnstileResolve(token);
                    turnstileResolve = null;
                    clearTimeout(turnstileTimeout);
                }
            },
            'error-callback': function (errorCode) {
                console.warn('[Turnstile] error-callback:', errorCode);
                if (turnstileResolve) {
                    turnstileResolve(null);
                    turnstileResolve = null;
                    clearTimeout(turnstileTimeout);
                }
            },
            'expired-callback': function () {
                console.warn('[Turnstile] expired-callback');
                if (turnstileResolve) {
                    turnstileResolve(null);
                    turnstileResolve = null;
                    clearTimeout(turnstileTimeout);
                }
            }
        });
    }


    function initTurnstile() {
        if (!turnstileKey || !form) return;

        const container = document.createElement('div');
        container.id = 'turnstile-container';
        container.style.display = 'none';
        form.appendChild(container);

        if (window.turnstile && typeof turnstile.render === 'function') {
            turnstileWidgetId = renderTurnstileWidget(container);
        } else {
            const check = setInterval(() => {
                if (window.turnstile && typeof turnstile.render === 'function') {
                    clearInterval(check);
                    turnstileWidgetId = renderTurnstileWidget(container);
                }
            }, 200);
        }
    }

    function getTurnstileToken() {
        if (!turnstileKey || !window.turnstile || turnstileWidgetId === null) {
            return Promise.resolve(null);
        }

        return new Promise(resolve => {
            turnstileResolve = resolve;
            try {
                turnstile.execute(turnstileWidgetId);
                // fallback timeout
                turnstileTimeout = setTimeout(() => {
                    if (turnstileResolve) {
                        turnstileResolve(null);
                        turnstileResolve = null;
                    }
                }, 15000);
            } catch (e) {
                turnstileResolve = null;
                resolve(null);
            }
        });
    }

    // Inicializa widget Turnstile (si hay key)
    initTurnstile();

    setupCentroAutocomplete();
    setupEquipoAutocomplete();

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!validateCurrentSection()) return;

            const submitButton = btnSubmit;
            const originalText = submitButton ? submitButton.innerHTML : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<div class="spinner"></div>';
            }

            try {
                // Obtener token de Turnstile (invisible)
                let token = null;
                if (turnstileKey && window.turnstile) {
                    token = await getTurnstileToken();
                }
                console.log('[Turnstile] token obtenido:', token);

                const formData = new FormData(form);
                if (token) formData.append('turnstile_token', token);

                const response = await fetch('/process/procesar_ticket.php', {
                    method: 'POST',
                    body: formData,
                });



                const result = await response.json();

                if (result.success) {
                    showSuccessModal(result.ticket_number);
                    form.reset();
                    currentSection = 0;
                    resetCentroSelection(true);
                    showSection(0);
                } else {
                    throw new Error(result.message || 'Error al procesar el ticket');
                }
            } catch (err) {
                console.error('Error completo:', err);
                alert('Error: ' + err.message);
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            }
        });
    }

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
                <p class="text-sm text-gray-500 mb-6">Recibirá una confirmación por correo electrónico con los detalles de su ticket.</p>
                <button type="button" onclick="this.closest('.fixed').remove()" class="btn-primary text-white px-6 py-3 rounded-lg w-full">Cerrar</button>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showSection(0);
});
