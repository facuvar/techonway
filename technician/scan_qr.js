/**
 * Custom JavaScript for scan_qr.php
 * This is a standalone implementation without dependencies on main.js
 */

// Variables
let mapInstance = null;
let currentPosition = null;
let clientPosition = null;
let distanceToClient = null;
let technicianMarker = null;
let clientMarker = null;
let visitInProgress = false;
let currentVisitId = null;
let locationValid = false;
let html5QrCode = null;
let mapInitialized = false;

// Elements
const startScanBtn = document.getElementById('start-scan');
const stopScanBtn = document.getElementById('stop-scan');
const scanResult = document.getElementById('scan-result');
const scanError = document.getElementById('scan-error');
const errorMessage = document.getElementById('error-message');
const ticketInfo = document.getElementById('ticket-info');
const locationStatus = document.getElementById('location-status');
const distanceInfo = document.getElementById('distance-info');
const distanceValue = document.getElementById('distance-value');
const startVisitForm = document.getElementById('start-visit-form');
const endVisitForm = document.getElementById('end-visit-form');
const ticketIdInput = document.getElementById('ticket-id');
const visitIdInput = document.getElementById('visit-id');
const processingOverlay = document.getElementById('processing');
const processingMessage = document.getElementById('processing-message');

// Initialize map
function initMap() {
    try {
        console.log('Initializing map...');
        
        // Get map container
        const mapContainer = document.getElementById('scan-map');
        
        // Check if map container exists
        if (!mapContainer) {
            console.error('Map container not found');
            return false;
        }
        
        // Clear the map container
        mapContainer.innerHTML = '';
        
        // Create the map
        mapInstance = L.map('scan-map').setView([-34.603722, -58.381592], 13);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapInstance);
        
        // Force map to recalculate size
        setTimeout(() => {
            if (mapInstance) {
                mapInstance.invalidateSize();
            }
        }, 500);
        
        console.log('Map initialized successfully');
        mapInitialized = true;
        return true;
    } catch (e) {
        console.error('Error initializing map:', e);
        return false;
    }
}

// Get current location
function getCurrentLocation() {
    locationStatus.className = 'alert alert-warning';
    locationStatus.innerHTML = '<i class="bi bi-geo-alt"></i> ' + (window.I18N?.scanQr?.getting || 'Obteniendo su ubicación...');
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                console.log('Got current position:', position.coords);
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                // Ensure map is initialized before updating
                if (!mapInitialized) {
                    initMap();
                }
                
                updateMap();
                updateLocationStatus();
            },
            (error) => {
                console.error('Error getting location:', error);
                locationStatus.className = 'alert alert-danger';
                locationStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (window.I18N?.scanQr?.error || 'Error al obtener su ubicación. Por favor, permita el acceso a su ubicación.');
            },
            { 
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        locationStatus.className = 'alert alert-danger';
        locationStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (window.I18N?.common?.geoUnsupported || 'Su navegador no soporta geolocalización.');
    }
}

// Update map with current location
function updateMap() {
    if (!mapInitialized || !mapInstance) {
        console.log('Map not initialized, initializing now...');
        if (!initMap()) {
            console.error('Failed to initialize map');
            return;
        }
    }
    
    console.log('Updating map...');
    console.log('Current position:', currentPosition);
    console.log('Client position:', clientPosition);
    
    // Force map to recalculate size
    mapInstance.invalidateSize();
    
    // Clear existing markers
    mapInstance.eachLayer(function(layer) {
        if (layer instanceof L.Marker || layer instanceof L.Polyline) {
            mapInstance.removeLayer(layer);
        }
    });
    
    // Re-add the base tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapInstance);
    
    // Add technician marker if available
    if (currentPosition) {
        technicianMarker = L.marker([currentPosition.lat, currentPosition.lng])
            .addTo(mapInstance)
            .bindPopup(window.I18N?.scanQr?.yourLocation || 'Su ubicación actual');
        
        console.log('Added technician marker at:', currentPosition);
    }
    
    // Add client marker if available
    if (clientPosition) {
        clientMarker = L.marker([clientPosition.lat, clientPosition.lng])
            .addTo(mapInstance)
            .bindPopup(window.I18N?.scanQr?.clientLocation || 'Ubicación del cliente');
        
        console.log('Added client marker at:', clientPosition);
    }
    
    // Draw line between technician and client
    if (currentPosition && clientPosition) {
        // Calculate distance
        distanceToClient = calculateDistance(
            currentPosition.lat, currentPosition.lng,
            clientPosition.lat, clientPosition.lng
        );
        
        console.log('Calculated distance:', distanceToClient, 'meters');
        
        // Update distance info
        distanceInfo.classList.remove('d-none');
        distanceValue.textContent = Math.round(distanceToClient);
        
        // Add debug information to the page
        distanceInfo.innerHTML = `
            <p>${window.I18N?.scanQr?.distanceLabel || 'Distancia al cliente:'} <span id="distance-value">${Math.round(distanceToClient)}</span> ${window.I18N?.scanQr?.distanceSuffix || 'metros'}</p>
            <div class="alert alert-info">
                <strong>${window.I18N?.scanQr?.debugTitle || 'Información de depuración:'}</strong><br>
                ${window.I18N?.scanQr?.yourPosition || 'Tu posición'}: ${currentPosition.lat.toFixed(7)}, ${currentPosition.lng.toFixed(7)}<br>
                ${window.I18N?.scanQr?.clientPosition || 'Posición del cliente'}: ${clientPosition.lat.toFixed(7)}, ${clientPosition.lng.toFixed(7)}
            </div>
        `;
        
        // Fit bounds to show both markers
        const bounds = L.latLngBounds(
            [currentPosition.lat, currentPosition.lng],
            [clientPosition.lat, clientPosition.lng]
        );
        mapInstance.fitBounds(bounds);
        
        // Draw line between points
        L.polyline([
            [currentPosition.lat, currentPosition.lng],
            [clientPosition.lat, clientPosition.lng]
        ], {color: 'blue', dashArray: '5, 10'}).addTo(mapInstance);
        
        console.log('Added route line between markers');
    } else if (currentPosition) {
        // Only technician position available
        mapInstance.setView([currentPosition.lat, currentPosition.lng], 15);
    } else if (clientPosition) {
        // Only client position available
        mapInstance.setView([clientPosition.lat, clientPosition.lng], 15);
    }
    
    // Force map to recalculate size again
    setTimeout(() => {
        if (mapInstance) {
            mapInstance.invalidateSize();
        }
    }, 500);
}

// Update location status based on distance to client
function updateLocationStatus() {
    if (!currentPosition || !clientPosition) {
        locationStatus.className = 'alert alert-warning';
        locationStatus.innerHTML = '<i class="bi bi-geo-alt"></i> Esperando a obtener su ubicación...';
        return;
    }
    
    // Debug information
    console.log('Posición actual:', currentPosition);
    console.log('Posición del cliente:', clientPosition);
    
    // Calculate distance
    distanceToClient = calculateDistance(
        currentPosition.lat, currentPosition.lng,
        clientPosition.lat, clientPosition.lng
    );
    
    console.log('Distancia calculada:', distanceToClient, 'metros');
    
    // Update distance info
    distanceInfo.classList.remove('d-none');
    distanceValue.textContent = Math.round(distanceToClient);
    
    // Límite de distancia (aumentado para pruebas)
    const distanceLimit = 2000; // 2 km para pruebas (normalmente sería 100 metros)
    
    // Update status based on distance
    if (distanceToClient <= distanceLimit) {  
        locationStatus.className = 'alert alert-success';
        locationStatus.innerHTML = '<i class="bi bi-check-circle"></i> ' + (window.I18N?.scanQr?.withinTest || 'Usted se encuentra dentro del rango permitido (modo prueba: 2 km)');
        locationValid = true;
    } else {
        locationStatus.className = 'alert alert-danger';
        locationStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + (window.I18N?.scanQr?.outsideTest || 'Usted se encuentra fuera del rango permitido. Debe estar a menos de 2 km del cliente (modo prueba).');
        locationValid = false;
    }
    
    // Update map
    updateMap();
}

// Calculate distance between two points in meters (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    
    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    
    return R * c; // Distance in meters
}

// Set client position
function setClientPosition(lat, lng) {
    clientPosition = {
        lat: parseFloat(lat),
        lng: parseFloat(lng)
    };
    
    console.log('Client position set:', clientPosition);
    
    // Ensure map is initialized before updating
    if (!mapInitialized) {
        initMap();
    }
    
    updateMap();
    updateLocationStatus();
}

// Initialize QR Code scanner
function initScanner() {
    html5QrCode = new Html5Qrcode("reader");
    
    startScanBtn.addEventListener('click', startScanner);
    stopScanBtn.addEventListener('click', stopScanner);
    
    // Check if there's an active visit for this technician
    checkActiveVisit();
    
    // Initialize form event listeners
    document.getElementById('form-start-visit').addEventListener('submit', startVisit);
    document.getElementById('form-end-visit').addEventListener('submit', endVisit);
    
    // Toggle between success and failure fields
    document.querySelectorAll('input[name="completion_status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'success') {
                document.getElementById('success-fields').classList.remove('d-none');
                document.getElementById('failure-fields').classList.add('d-none');
                document.getElementById('failure-reason').removeAttribute('required');
                document.getElementById('comments').setAttribute('required', 'required');
            } else {
                document.getElementById('success-fields').classList.add('d-none');
                document.getElementById('failure-fields').classList.remove('d-none');
                document.getElementById('comments').removeAttribute('required');
                document.getElementById('failure-reason').setAttribute('required', 'required');
            }
        });
    });
    
    // Auto-process from select_ticket.php or direct links
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    const ticketId = urlParams.get('ticket_id');
    const visitId = urlParams.get('visit_id');
    const lat = urlParams.get('lat');
    const lng = urlParams.get('lng');
    
    // If we have action and ticket_id/visit_id but no location, get the location first
    if (action && (ticketId || visitId) && (!lat || !lng)) {
        // Show a message to the user
        document.getElementById('scanner-container').classList.add('d-none');
        document.getElementById('auto-process').classList.remove('d-none');
        
        // Get current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const newLat = position.coords.latitude;
                    const newLng = position.coords.longitude;
                    
                    // Update URL with location parameters
                    let newUrl = window.location.href;
                    if (newUrl.includes('?')) {
                        newUrl += `&lat=${newLat}&lng=${newLng}`;
                    } else {
                        newUrl += `?lat=${newLat}&lng=${newLng}`;
                    }
                    
                    // Redirect to the same page with location parameters
                    window.location.href = newUrl;
                },
                function(error) {
                    // Show error message
                    document.getElementById('scanner-container').classList.remove('d-none');
                    document.getElementById('auto-process').classList.add('d-none');
                    errorMessage.textContent = 'Error al obtener la ubicación. Por favor, permita el acceso a su ubicación.';
                    scanError.classList.remove('d-none');
                },
                { 
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            // Show error message
            document.getElementById('scanner-container').classList.remove('d-none');
            document.getElementById('auto-process').classList.add('d-none');
            errorMessage.textContent = 'Su navegador no soporta geolocalización.';
            scanError.classList.remove('d-none');
        }
    }
    // If we have action and ticket_id/visit_id and location, process automatically
    else if (action && (ticketId || visitId) && lat && lng) {
        console.log('Auto-processing with parameters');
        
        // Set current position from URL parameters
        currentPosition = {
            lat: parseFloat(lat),
            lng: parseFloat(lng)
        };
        
        console.log('Current position set from URL:', currentPosition);
        
        // Process based on action
        setTimeout(() => {
            if (action === 'start' && ticketId) {
                console.log('Processing start action with ticket ID:', ticketId);
                
                // Get ticket information directly
                fetch(`../api/get_ticket.php?id=${ticketId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const ticket = data.ticket;
                            
                            // Set client position
                            setClientPosition(
                                parseFloat(ticket.client_latitude),
                                parseFloat(ticket.client_longitude)
                            );
                            
                            // Show start visit form
                            document.getElementById('ticket-id').value = ticketId;
                            showStartVisitForm();
                        } else {
                            errorMessage.textContent = data.message || 'Error al obtener información del ticket';
                            scanError.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        errorMessage.textContent = 'Error de conexión al obtener información del ticket';
                        scanError.classList.remove('d-none');
                    });
            } else if (action === 'end') {
                console.log('Processing end action');
                
                // Verificar si tenemos visit_id o ticket_id
                if (visitId) {
                    console.log('Processing end action with visit ID:', visitId);
                    
                    // Get visit information
                    fetch(`../api/get_visit.php?id=${visitId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Set client position from visit data
                                setClientPosition(
                                    parseFloat(data.visit.client_latitude),
                                    parseFloat(data.visit.client_longitude)
                                );
                                
                                // Show end visit form
                                document.getElementById('visit-id').value = visitId;
                                showEndVisitForm();
                            } else {
                                errorMessage.textContent = data.message || 'Error al obtener información de la visita';
                                scanError.classList.remove('d-none');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            errorMessage.textContent = 'Error de conexión al obtener información de la visita';
                            scanError.classList.remove('d-none');
                        });
                } else if (ticketId) {
                    console.log('Processing end action with ticket ID:', ticketId);
                    
                    // Buscar la visita activa para este ticket
                    fetch(`../api/get_active_visit.php?ticket_id=${ticketId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.visit) {
                                // Set client position from visit data
                                setClientPosition(
                                    parseFloat(data.visit.client_latitude),
                                    parseFloat(data.visit.client_longitude)
                                );
                                
                                // Show end visit form
                                document.getElementById('visit-id').value = data.visit.id;
                                showEndVisitForm();
                            } else {
                                errorMessage.textContent = data.message || 'No se encontró una visita activa para este ticket';
                                scanError.classList.remove('d-none');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            errorMessage.textContent = 'Error de conexión al obtener información de la visita';
                            scanError.classList.remove('d-none');
                        });
                } else {
                    errorMessage.textContent = 'Se requiere ID de visita o ID de ticket para finalizar una visita';
                    scanError.classList.remove('d-none');
                }
            }
        }, 1000);
    }
    // If we have action and ticket_id/visit_id but no location, get the location first
    else {
        // Initialize scanner normally
        startScanBtn.classList.remove('d-none');
    }
}

// Start QR Code scanner
function startScanner() {
    startScanBtn.classList.add('d-none');
    stopScanBtn.classList.remove('d-none');
    
    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        onScanSuccess,
        onScanFailure
    ).catch(err => {
        console.error('Error starting scanner:', err);
        errorMessage.textContent = 'Error al iniciar el escáner. Por favor, permita el acceso a la cámara e intente nuevamente.';
        scanError.classList.remove('d-none');
        startScanBtn.classList.remove('d-none');
        stopScanBtn.classList.add('d-none');
    });
}

// Stop QR Code scanner
function stopScanner() {
    html5QrCode.stop().then(() => {
        startScanBtn.classList.remove('d-none');
        stopScanBtn.classList.add('d-none');
    }).catch(err => {
        console.error('Error stopping scanner:', err);
    });
}

// Handle successful QR code scan
function onScanSuccess(decodedText, decodedResult) {
    // Stop scanner
    stopScanner();
    
    // Hide error message
    scanError.classList.add('d-none');
    
    // Process QR code
    try {
        // Check if it's a ticket QR code
        if (decodedText.startsWith('TICKET:')) {
            const ticketId = decodedText.replace('TICKET:', '');
            processTicketQR(ticketId);
        } else {
            errorMessage.textContent = 'Código QR no válido. Por favor, escanee un código QR de ticket.';
            scanError.classList.remove('d-none');
        }
    } catch (error) {
        console.error('Error processing QR code:', error);
        errorMessage.textContent = 'Error al procesar el código QR. Por favor, intente nuevamente.';
        scanError.classList.remove('d-none');
    }
}

// Handle QR code scan failure
function onScanFailure(error) {
    // Do nothing on failure
}

// Process ticket QR code
function processTicketQR(ticketId) {
    showProcessing(`Procesando ticket #${ticketId}...`);
    
    // Get ticket information
    fetch(`../api/get_ticket.php?id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ticket = data.ticket;
                
                // Set client position
                setClientPosition(
                    parseFloat(ticket.client_latitude),
                    parseFloat(ticket.client_longitude)
                );
                
                // Show appropriate form based on active visit
                if (data.active_visit) {
                    // Show end visit form
                    document.getElementById('visit-id').value = data.active_visit.id;
                    showEndVisitForm();
                } else {
                    // Show start visit form
                    document.getElementById('ticket-id').value = ticketId;
                    showStartVisitForm();
                }
                
                hideProcessing();
            } else {
                errorMessage.textContent = data.message || 'Error al obtener información del ticket';
                scanError.classList.remove('d-none');
                hideProcessing();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.textContent = 'Error de conexión al obtener información del ticket';
            scanError.classList.remove('d-none');
            hideProcessing();
        });
}

// Check if there's an active visit for this technician
function checkActiveVisit() {
    fetch('../api/check_active_visit.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.active_visit) {
                visitInProgress = true;
                currentVisitId = data.active_visit.id;
                
                // Show alert about active visit
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-info alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bi bi-info-circle"></i> Tiene una visita activa en curso. 
                    <a href="active_visit.php?id=${data.active_visit.id}" class="alert-link">Ver detalles</a> o 
                    <a href="scan_qr.php?action=end&visit_id=${data.active_visit.id}" class="alert-link">Finalizar visita</a>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container-fluid').prepend(alertDiv);
            }
        })
        .catch(error => {
            console.error('Error checking active visit:', error);
        });
}

// Start a new visit
function startVisit(e) {
    e.preventDefault();
    showProcessing('Iniciando visita...');
    
    const formData = new FormData(e.target);
    
    // Add current location to form data
    if (currentPosition) {
        formData.append('latitude', currentPosition.lat);
        formData.append('longitude', currentPosition.lng);
    } else {
        hideProcessing();
        alert('No se pudo obtener su ubicación actual. Por favor, permita el acceso a su ubicación e intente nuevamente.');
        return;
    }
    
    // Add client position to form data
    if (clientPosition) {
        formData.append('client_latitude', clientPosition.lat);
        formData.append('client_longitude', clientPosition.lng);
        formData.append('client_distance', distanceToClient);
    }
    
    // Debug information
    console.log('Enviando datos:', {
        ticket_id: formData.get('ticket_id'),
        latitude: formData.get('latitude'),
        longitude: formData.get('longitude'),
        client_latitude: formData.get('client_latitude'),
        client_longitude: formData.get('client_longitude'),
        client_distance: formData.get('client_distance'),
        start_notes: formData.get('start_notes')
    });
    
    // Send request to start visit
    fetch('../api/start_visit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta del servidor:', response);
        return response.text().then(text => {
            console.log('Texto de respuesta completo:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.log('Texto recibido:', text);
                throw new Error('Respuesta del servidor inválida: ' + text);
            }
        });
    })
    .then(data => {
        hideProcessing();
        console.log('Datos recibidos:', data);
        
        if (data.success) {
            // Show success message
            alert('Visita iniciada correctamente');
            
            // Redirect to active visit page
            window.location.href = '../technician/active_visit.php?id=' + data.visit_id;
        } else {
            // Show error message
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        hideProcessing();
        console.error('Error completo:', error);
        alert('Error de conexión al servidor: ' + error.message + '. Verifique la consola para más detalles.');
    });
}

// End a visit
function endVisit(e) {
    e.preventDefault();
    showProcessing('Finalizando visita...');
    
    const formData = new FormData(e.target);
    
    // Add current location to form data
    if (currentPosition) {
        formData.append('latitude', currentPosition.lat);
        formData.append('longitude', currentPosition.lng);
    } else {
        hideProcessing();
        alert('No se pudo obtener su ubicación actual. Por favor, permita el acceso a su ubicación e intente nuevamente.');
        return;
    }
    
    // Add client position to form data
    if (clientPosition) {
        formData.append('client_latitude', clientPosition.lat);
        formData.append('client_longitude', clientPosition.lng);
        formData.append('client_distance', distanceToClient);
    }
    
    // Debug information
    console.log('Enviando datos para finalizar visita:', {
        visit_id: formData.get('visit_id'),
        latitude: formData.get('latitude'),
        longitude: formData.get('longitude'),
        client_latitude: formData.get('client_latitude'),
        client_longitude: formData.get('client_longitude'),
        client_distance: formData.get('client_distance'),
        comments: formData.get('comments')
    });
    
    // Send request to end visit
    fetch('../api/end_visit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta del servidor:', response);
        return response.text().then(text => {
            console.log('Texto de respuesta completo:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.log('Texto recibido:', text);
                throw new Error('Respuesta del servidor inválida: ' + text);
            }
        });
    })
    .then(data => {
        hideProcessing();
        console.log('Datos recibidos:', data);
        
        if (data.success) {
            // Show success message
            alert('Visita finalizada correctamente');
            
            // Redirect to completed visits page
            window.location.href = '../technician/completed-visits.php';
        } else {
            // Show error message
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        hideProcessing();
        console.error('Error completo:', error);
        alert('Error de conexión al servidor: ' + error.message + '. Verifique la consola para más detalles.');
    });
}

// Show processing overlay
function showProcessing(message) {
    processingMessage.textContent = message || 'Procesando...';
    processingOverlay.classList.remove('d-none');
}

// Hide processing overlay
function hideProcessing() {
    processingOverlay.classList.add('d-none');
}

// Show start visit form
function showStartVisitForm() {
    document.getElementById('start-visit-form').classList.remove('d-none');
    document.getElementById('end-visit-form').classList.add('d-none');
    document.getElementById('scan-result').classList.remove('d-none');
}

// Show end visit form
function showEndVisitForm() {
    document.getElementById('end-visit-form').classList.remove('d-none');
    document.getElementById('start-visit-form').classList.add('d-none');
    document.getElementById('scan-result').classList.remove('d-none');
}

// Procesar parámetros de URL
function processUrlParams() {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    const ticketId = urlParams.get('ticket_id');
    const visitId = urlParams.get('visit_id');
    const lat = urlParams.get('lat');
    const lng = urlParams.get('lng');
    
    console.log('Parámetros de URL:', { action, ticketId, visitId, lat, lng });
    
    // Si se proporcionan coordenadas en la URL, usarlas
    if (lat && lng) {
        console.log('Usando coordenadas de la URL:', lat, lng);
        currentPosition = {
            lat: parseFloat(lat),
            lng: parseFloat(lng)
        };
    }
    
    // Si se proporciona un ticket_id, procesar automáticamente
    if (ticketId) {
        console.log('Procesando ticket desde URL:', ticketId);
        showProcessing(`Procesando ticket #${ticketId}...`);
        
        // Esperar a que se inicialice el mapa
        const waitForMap = setInterval(() => {
            if (mapInitialized) {
                clearInterval(waitForMap);
                processTicketQR(ticketId);
            }
        }, 500);
    }
    
    // Si se proporciona un visit_id y la acción es 'end', mostrar formulario de finalización
    if (visitId && action === 'end') {
        console.log('Procesando finalización de visita desde URL:', visitId);
        showProcessing('Cargando información de la visita...');
        
        // Esperar a que se inicialice el mapa
        const waitForMap = setInterval(() => {
            if (mapInitialized) {
                clearInterval(waitForMap);
                
                // Obtener información del ticket asociado a la visita
                fetch(`../api/get_visit.php?id=${visitId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Establecer posición del cliente
                            setClientPosition(
                                parseFloat(data.visit.client_latitude),
                                parseFloat(data.visit.client_longitude)
                            );
                            
                            // Mostrar formulario de finalización
                            document.getElementById('visit-id').value = visitId;
                            showEndVisitForm();
                            hideProcessing();
                        } else {
                            errorMessage.textContent = data.message || 'Error al obtener información de la visita';
                            scanError.classList.remove('d-none');
                            hideProcessing();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        errorMessage.textContent = 'Error de conexión al obtener información de la visita';
                        scanError.classList.remove('d-none');
                        hideProcessing();
                    });
            }
        }, 500);
    }
}

// Initialize when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    
    // Initialize map with delay to ensure DOM is ready
    setTimeout(function() {
        initMap();
        // Get current location after map is initialized
        setTimeout(function() {
            getCurrentLocation();
        }, 500);
    }, 1000);
    
    // Initialize scanner
    initScanner();
    
    // Procesar parámetros de URL
    processUrlParams();
});
