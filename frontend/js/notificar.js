/**
 * notificar.js - Control Dinámico de Notificaciones
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- Referencias UI ---
    const ui = {
        buscarU: document.getElementById('buscarUsuario'),
        resU: document.getElementById('searchResults'),
        destL: document.getElementById('destinatariosList'),
        destI: document.getElementById('destinatariosInput'),
        
        tipoBtns: document.querySelectorAll('.tipo-btn'),
        selCat: document.getElementById('selectCategoria'),
        selAct: document.getElementById('selectActividad'),
        buscarA: document.getElementById('buscarActividad'),
        resA: document.getElementById('actividadSearchResults'),
        
        range: document.getElementById('cantidadRange'),
        badge: document.getElementById('rangoValorBadge'),
        maxLbl: document.getElementById('rangoMax'),
        hiddenC: document.getElementById('cantidadHidden'),
        
        btnAdd: document.getElementById('btnAgregarActividad'),
        actL: document.getElementById('actividadesList'),
        actI: document.getElementById('actividadesInput'),
        
        form: document.getElementById('notificarForm'),
        btnSub: document.getElementById('btnSubmit')
    };

    let selectedUsers = [];
    let selectedActs = [];

    // ==========================================
    // 1. LÓGICA DEL SCROLL (RANGO DINÁMICO)
    // ==========================================
    function actualizarScroll() {
        const tipo = document.querySelector('.tipo-btn.active')?.dataset?.tipo || 'merito';
        const actId = ui.selAct.value;
        
        // Detectar grado del estudiante (prioriza al primero de la lista)
        const primerEst = selectedUsers[0];
        const grado = primerEst ? (primerEst.grado || '10mo') : '10mo';

        if (!actId) {
            resetScroll();
            return;
        }

        const catalogo = (tipo === 'merito') ? catalogoMeritos : catalogoDemeritos;
        
        // Buscar en el catálogo por ID (comparación flexible string/number)
        const item = catalogo.find(x => String(x.id) === String(actId));

        if (item) {
            let maxVal = 1;
            
            if (tipo === 'demerito') {
                // Selección de columna según el grado real del estudiante
                if (grado === '11no' || grado === '12mo') {
                    // Para 11no o 12mo usar columna demeritos_11_12, si no existe usar demeritos_10mo
                    maxVal = parseInt(item.demeritos_11_12) || parseInt(item.demeritos_10mo) || 1;
                } else {
                    // Para 10mo o cualquier otro caso por defecto
                    maxVal = parseInt(item.demeritos_10mo) || 1;
                }
            } else {
                // Para méritos usar el campo cantidad
                maxVal = parseInt(item.cantidad) || 1;
            }

            // Actualizar el DOM del Range
            ui.range.max = maxVal;
            ui.range.min = 1;
            
            // Ajustar valor actual si excede el nuevo máximo
            if (parseInt(ui.range.value) > maxVal) {
                ui.range.value = maxVal;
            }
            if (parseInt(ui.range.value) < 1) {
                ui.range.value = 1;
            }
            
            ui.maxLbl.textContent = maxVal;
            ui.badge.textContent = ui.range.value;
            ui.hiddenC.value = ui.range.value;
            
            // Actualizar clase del badge según tipo
            ui.badge.className = 'rango-valor-badge';
            if (tipo === 'merito') {
                ui.badge.classList.add('merito');
            } else if (tipo === 'demerito') {
                ui.badge.classList.add('demerito');
            } else {
                ui.badge.classList.add('default');
            }
        } else {
            // Si no encuentra el item en el catálogo, resetear
            resetScroll();
        }
    }

    function resetScroll() {
        ui.range.max = 1;
        ui.range.min = 1;
        ui.range.value = 1;
        ui.maxLbl.textContent = '1';
        ui.badge.textContent = '1';
        ui.hiddenC.value = '1';
        ui.badge.className = 'rango-valor-badge default';
    }

    // ==========================================
    // 2. BUSCADORES (ESTUDIANTES Y ACTIVIDADES)
    // ==========================================
    
    // Buscador de Estudiantes (AJAX)
    let searchTimeout;
    ui.buscarU.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const q = e.target.value.trim();
        
        if (q.length < 2) { 
            ui.resU.style.display = 'none'; 
            ui.resU.innerHTML = '';
            return; 
        }
        
        searchTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`buscar_usuarios.php?q=${encodeURIComponent(q)}`);
                if (!res.ok) throw new Error('Error en la búsqueda');
                const data = await res.json();
                
                ui.resU.innerHTML = '';
                
                if (data.length === 0) {
                    ui.resU.innerHTML = '<div class="search-empty">No se encontraron estudiantes</div>';
                } else {
                    data.forEach(u => {
                        const div = document.createElement('div');
                        div.className = 'search-result-item';
                        div.innerHTML = `
                            <div class="search-result-info">
                                <span class="search-result-name">${u.nombre} ${u.apellidos}</span>
                                <span class="search-result-details">CI: ${u.ci || u.CI || 'N/A'} • ${u.grado || 'Sin grado'}</span>
                            </div>
                            <i class="fas fa-plus-circle" style="color: #667eea;"></i>
                        `;
                        div.onclick = () => {
                            if (!selectedUsers.find(x => x.id == u.id)) {
                                selectedUsers.push(u);
                                renderUsers();
                            }
                            ui.buscarU.value = '';
                            ui.resU.style.display = 'none';
                            ui.resU.innerHTML = '';
                        };
                        ui.resU.appendChild(div);
                    });
                }
                ui.resU.style.display = 'block';
            } catch (err) { 
                console.error("Error buscando usuarios:", err); 
                ui.resU.innerHTML = '<div class="search-empty">Error al buscar</div>';
                ui.resU.style.display = 'block';
            }
        }, 300);
    });

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-box')) {
            ui.resU.style.display = 'none';
            ui.resA.style.display = 'none';
        }
    });

    // Buscador de Actividades (Local)
    let searchActTimeout;
    ui.buscarA.addEventListener('input', (e) => {
        clearTimeout(searchActTimeout);
        const q = e.target.value.toLowerCase().trim();
        const tipo = document.querySelector('.tipo-btn.active')?.dataset?.tipo || 'merito';
        const catalogo = (tipo === 'merito') ? catalogoMeritos : catalogoDemeritos;

        if (q.length < 2) { 
            ui.resA.style.display = 'none'; 
            ui.resA.innerHTML = '';
            return; 
        }

        searchActTimeout = setTimeout(() => {
            const filtered = catalogo.filter(a => 
                (a.nombre && a.nombre.toLowerCase().includes(q)) || 
                (a.categoria && a.categoria.toLowerCase().includes(q)) ||
                (a.causa && a.causa.toLowerCase().includes(q)) ||
                (a.falta && a.falta.toLowerCase().includes(q))
            );

            ui.resA.innerHTML = '';
            
            if (filtered.length === 0) {
                ui.resA.innerHTML = '<div class="search-empty">No se encontraron actividades</div>';
            } else {
                filtered.forEach(a => {
                    const nombre = a.nombre || a.causa || a.falta || 'Sin nombre';
                    const categoria = a.categoria || 'Sin categoría';
                    const puntos = tipo === 'merito' ? (a.cantidad || 1) : (a.demeritos_10mo || 1);
                    
                    const div = document.createElement('div');
                    div.className = 'actividad-search-item';
                    div.innerHTML = `
                        <div class="actividad-search-info">
                            <div class="actividad-search-nombre">${nombre}</div>
                            <span class="actividad-search-categoria">${categoria}</span>
                        </div>
                        <span class="actividad-search-valor" style="color: ${tipo === 'merito' ? '#10b981' : '#ef4444'};">
                            ${tipo === 'merito' ? '+' : '-'}${puntos} pts
                        </span>
                    `;
                    div.onclick = () => {
                        // Seleccionar categoría y actividad en los selects
                        ui.selCat.value = categoria;
                        ui.selCat.dispatchEvent(new Event('change'));
                        
                        // Esperar a que se actualice el select de actividades
                        setTimeout(() => {
                            ui.selAct.value = String(a.id);
                            ui.selAct.dispatchEvent(new Event('change'));
                            actualizarScroll();
                        }, 100);
                        
                        ui.buscarA.value = '';
                        ui.resA.style.display = 'none';
                        ui.resA.innerHTML = '';
                    };
                    ui.resA.appendChild(div);
                });
            }
            ui.resA.style.display = 'block';
        }, 300);
    });

    // ==========================================
    // 3. GESTIÓN DE CATÁLOGOS Y SELECCIÓN
    // ==========================================
    function initCategorias() {
        const tipo = document.querySelector('.tipo-btn.active')?.dataset?.tipo || 'merito';
        const catalogo = (tipo === 'merito') ? catalogoMeritos : catalogoDemeritos;
        const cats = [...new Set(catalogo.map(i => i.categoria).filter(Boolean))];
        
        ui.selCat.innerHTML = '<option value="">Selecciona categoría</option>';
        cats.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c; 
            opt.textContent = c;
            ui.selCat.appendChild(opt);
        });
        actualizarSelectActividades();
    }

    function actualizarSelectActividades() {
        const tipo = document.querySelector('.tipo-btn.active')?.dataset?.tipo || 'merito';
        const cat = ui.selCat.value;
        const catalogo = (tipo === 'merito') ? catalogoMeritos : catalogoDemeritos;

        ui.selAct.innerHTML = '<option value="">Selecciona actividad</option>';
        
        const itemsFiltrados = cat ? catalogo.filter(i => i.categoria === cat) : catalogo;
        
        itemsFiltrados.forEach(i => {
            const opt = document.createElement('option');
            opt.value = i.id;
            opt.textContent = i.nombre || i.causa || i.falta || 'Sin nombre';
            ui.selAct.appendChild(opt);
        });
        
        // Actualizar scroll después de cambiar actividades
        actualizarScroll();
    }

    // ==========================================
    // 4. ACCIONES DE LA LISTA Y ENVÍO
    // ==========================================
    function renderUsers() {
        ui.destL.innerHTML = '';
        
        if (selectedUsers.length === 0) {
            ui.destL.innerHTML = '<p class="empty-destinatarios" id="emptyDestinatarios">Busca y selecciona estudiantes</p>';
        } else {
            selectedUsers.forEach((u, index) => {
                const tag = document.createElement('div');
                tag.className = 'destinatario-tag';
                tag.dataset.grado = u.grado;
                tag.innerHTML = `
                    <span>${u.nombre} ${u.apellidos || ''} (${u.grado || 'Sin grado'})</span>
                    <i class="fas fa-times-circle"></i>
                `;
                tag.querySelector('i').onclick = (e) => {
                    e.stopPropagation();
                    selectedUsers = selectedUsers.filter(x => x.id != u.id);
                    renderUsers();
                };
                ui.destL.appendChild(tag);
            });
        }
        actualizarScroll();
        checkSubmit();
    }

    // Si hay destinatario preseleccionado
    if (typeof destinatarioPreseleccionado !== 'undefined' && destinatarioPreseleccionado) {
        selectedUsers.push(destinatarioPreseleccionado);
        renderUsers();
    }

    ui.btnAdd.onclick = () => {
        const id = ui.selAct.value;
        if (!id) {
            alert('Selecciona una actividad primero');
            return;
        }

        const nombre = ui.selAct.options[ui.selAct.selectedIndex].text;
        const tipo = document.querySelector('.tipo-btn.active')?.dataset?.tipo || 'merito';
        const categoria = ui.selCat.value;
        const pts = parseInt(ui.hiddenC.value) || 1;

        const actObj = { 
            id: id, 
            actividad_id: id,
            nombre: nombre, 
            tipo: tipo, 
            categoria: categoria,
            cantidad: pts 
        };
        selectedActs.push(actObj);

        const div = document.createElement('div');
        div.className = `actividad-tag ${tipo}`;
        div.style.cssText = `
            background: ${tipo === 'merito' ? '#d1fae5' : '#fee2e2'};
            border-left: 4px solid ${tipo === 'merito' ? '#10b981' : '#ef4444'};
        `;
        div.innerHTML = `
            <div>
                <div class="actividad-tag-nombre">${nombre}</div>
                <span class="actividad-tag-categoria">${categoria}</span>
            </div>
            <div class="actividad-tag-actions">
                <span style="font-weight: 700; color: ${tipo === 'merito' ? '#10b981' : '#ef4444'};">
                    ${tipo === 'merito' ? '+' : '-'}${pts} pts
                </span>
                <i class="fas fa-trash-alt btn-remove"></i>
            </div>
        `;
        
        div.querySelector('.btn-remove').onclick = () => {
            selectedActs = selectedActs.filter(a => a !== actObj);
            div.remove();
            checkSubmit();
        };

        ui.actL.appendChild(div);
        checkSubmit();
    };

    function checkSubmit() {
        const isValid = selectedUsers.length > 0 && selectedActs.length > 0;
        ui.btnSub.disabled = !isValid;
        ui.destI.value = JSON.stringify(selectedUsers);
        ui.actI.value = JSON.stringify(selectedActs);
    }

    // ==========================================
    // 5. EVENTOS
    // ==========================================
    
    // Cambio de tipo (mérito/demérito)
    ui.tipoBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            ui.tipoBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            initCategorias();
        });
    });

    // Cambio de categoría
    ui.selCat.addEventListener('change', actualizarSelectActividades);
    
    // Cambio de actividad específica
    ui.selAct.addEventListener('change', actualizarScroll);
    
    // Cambio en el range
    ui.range.addEventListener('input', () => {
        ui.badge.textContent = ui.range.value;
        ui.hiddenC.value = ui.range.value;
    });

    // Submit del formulario
    ui.form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        if (selectedUsers.length === 0) {
            alert('Selecciona al menos un estudiante');
            return;
        }
        if (selectedActs.length === 0) {
            alert('Agrega al menos una actividad');
            return;
        }
        
        document.getElementById('confirmResumen').innerHTML = `
            <div class="confirm-section">
                <h3><i class="fas fa-users"></i> Estudiantes (${selectedUsers.length})</h3>
                <div class="confirm-tags">
                    ${selectedUsers.map(u => `<span>${u.nombre} ${u.apellidos || ''} (${u.grado || 'Sin grado'})</span>`).join('')}
                </div>
            </div>
            <div class="confirm-section">
                <h3><i class="fas fa-tasks"></i> Actividades (${selectedActs.length})</h3>
                ${selectedActs.map(a => `
                    <div class="confirm-actividad ${a.tipo}">
                        <strong>${a.nombre}</strong>
                        <small>${a.categoria} • ${a.tipo === 'merito' ? '+' : '-'}${a.cantidad} pts</small>
                    </div>
                `).join('')}
            </div>
        `;
        document.getElementById('confirmModal').classList.add('show');
        document.getElementById('overlay').classList.add('show');
    });

    // Confirmar envío
    document.getElementById('btnConfirmarEnvio').addEventListener('click', async () => {
        const fd = new FormData(ui.form);
        
        // Agregar datos adicionales
        fd.append('tipo_notificador', 'cuenta');
        
        try {
            const r = await fetch('notificar_procesar.php', { 
                method: 'POST', 
                body: fd 
            });
            
            if (!r.ok) throw new Error('Error en la respuesta del servidor');
            
            const data = await r.json();
            
            if (data.success) {
                document.getElementById('confirmModal').classList.remove('show');
                document.getElementById('overlay').classList.remove('show');
                document.getElementById('successModal').classList.add('show');
                
                // Mostrar mensaje de éxito con detalles
                const successMsg = document.querySelector('#successModal p');
                if (successMsg && data.insertados) {
                    successMsg.textContent = `Se registraron ${data.insertados} notificaciones correctamente.`;
                }
            } else { 
                alert("Error: " + (data.message || 'Error desconocido')); 
            }
        } catch (e) { 
            console.error('Error:', e);
            alert("Error al procesar la solicitud. Verifica la conexión."); 
        }
    });

    // Cancelar confirmación
    document.getElementById('btnCancelarConfirm').addEventListener('click', () => {
        document.getElementById('confirmModal').classList.remove('show');
        document.getElementById('overlay').classList.remove('show');
    });

    // Cerrar modal de éxito
    document.querySelector('#successModal .btn-success')?.addEventListener('click', () => {
        window.location.href = 'index.php';
    });

    // Inicializar
    initCategorias();
    
    // Si hay destinatario preseleccionado, ya se agregó en renderUsers
    if (selectedUsers.length > 0) {
        actualizarScroll();
        checkSubmit();
    }
});