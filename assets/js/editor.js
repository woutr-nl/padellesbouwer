/**
 * Padel Les Editor
 * Visuele editor voor padel-lessen met canvas en drag-and-drop functionaliteit
 */

class PadelEditor {
    constructor() {
        this.canvas = document.getElementById('padelCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.currentTool = null;
        this.selectedItem = null;
        this.isDrawing = false;
        this.isDragging = false;
        this.dragStart = { x: 0, y: 0 };
        this.zoom = 1;
        this.offset = { x: 0, y: 0 };
        this.items = [];
        this.nextId = 1;
        this.selectedColor = '#4ecdc4';
        this.justFinishedRotating = false; // Flag om te voorkomen dat items worden toegevoegd na rotatie
        
        // Zet canvasgrootte altijd op 800x600
        this.canvas.width = 800;
        this.canvas.height = 600;
        
        // Padelbaan afbeelding
        this.courtImage = new Image();
        this.courtImage.src = 'assets/images/padelbaan.png';
        this.courtImageLoaded = false;
        this.courtImage.onload = () => {
            this.courtImageLoaded = true;
            this.draw();
        };
        
        this.init();
    }
    
    init() {
        // Check if lesData is available before initializing
        if (!window.lesData) {
            console.error('lesData is not available. Editor cannot be initialized.');
            alert('Fout: Les data niet beschikbaar. Probeer de pagina te herladen.');
            return;
        }
        
        console.log('Initializing editor with lesData:', window.lesData);
        
        this.setupCanvas();
        this.setupEventListeners();
        this.loadExistingItems();
        this.draw(); // This should draw all items including existing ones
    }
    
    setupCanvas() {
        // Geen dynamische resizing meer
    }
    
    resizeCanvas() {
        const container = this.canvas.parentElement;
        const rect = container.getBoundingClientRect();
        
        this.canvas.width = rect.width - 40;
        this.canvas.height = Math.min(600, rect.height - 40);
        
        this.draw();
    }
    
    setupEventListeners() {
        // Tool selectie
        document.querySelectorAll('[data-tool]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.selectTool(e.target.closest('[data-tool]').dataset.tool);
            });
        });
        
        // Canvas events
        this.canvas.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        this.canvas.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        this.canvas.addEventListener('mouseleave', (e) => this.handleMouseLeave(e));
        this.canvas.addEventListener('click', (e) => this.handleClick(e));
        this.canvas.addEventListener('contextmenu', (e) => this.handleContextMenu(e));
        
        // Zoom controls
        document.getElementById('zoomInBtn').addEventListener('click', () => this.zoomIn());
        document.getElementById('zoomOutBtn').addEventListener('click', () => this.zoomOut());
        document.getElementById('resetZoomBtn').addEventListener('click', () => this.resetZoom());
        
        // Action buttons
        document.getElementById('deleteBtn').addEventListener('click', () => this.deleteSelected());
        document.getElementById('clearBtn').addEventListener('click', () => this.clearAll());
        document.getElementById('editorSaveBtn').addEventListener('click', () => this.save());
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));

        // Color picker event
        const colorPicker = document.getElementById('colorPicker');
        if (colorPicker) {
            colorPicker.addEventListener('input', (e) => {
                this.selectedColor = e.target.value;
            });
        }
        // Preset kleurknoppen
        document.querySelectorAll('.preset-color-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const color = btn.getAttribute('data-color');
                this.selectedColor = color;
                if (colorPicker) colorPicker.value = color;
            });
        });
    }
    
    selectTool(tool) {
        this.currentTool = tool;
        
        // Update UI
        document.querySelectorAll('[data-tool]').forEach(btn => {
            btn.classList.remove('active');
        });
        const toolButton = document.querySelector(`[data-tool="${tool}"]`);
        if (toolButton) {
            toolButton.classList.add('active');
        }
        
        // Update cursor
        this.canvas.style.cursor = 'crosshair';
    }
    
    getMousePos(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: (e.clientX - rect.left) / this.zoom - this.offset.x,
            y: (e.clientY - rect.top) / this.zoom - this.offset.y
        };
    }
    
    handleMouseDown(e) {
        // Alleen linker muisknop (0) mag iets toevoegen/selecteren
        if (e.button !== 0) return;
        const pos = this.getMousePos(e);
        console.log('Mouse down at:', pos);
        console.log('Current state - isDrawing:', this.isDrawing, 'curvedPhase:', this.curvedPhase, 'currentTool:', this.currentTool);
        
        // --- Handle drag handles for selected line ---
        if (this.selectedItem && (this.selectedItem.type === 'lijn' || this.selectedItem.type === 'curved')) {
            const handle = this.getHandleAt(pos.x, pos.y, this.selectedItem);
            console.log('Checking handle for selected item:', handle);
            if (handle) {
                this.draggingHandle = handle;
                this.isDraggingHandle = true;
                console.log('Started dragging handle:', handle);
                return;
            }
        }
        
        // --- Handle rotation for selected flapje/hoekflapje ---
        if (this.selectedItem && (this.selectedItem.type === 'flapje' || this.selectedItem.type === 'hoekflapje')) {
            const rotationHandle = this.getRotationHandleAt(pos.x, pos.y, this.selectedItem);
            console.log('Checking rotation handle for selected item:', rotationHandle);
            if (rotationHandle === 'rotation') {
                this.isRotating = true;
                // Bereken de start hoek van de muis ten opzichte van het item
                const dx = pos.x - this.selectedItem.x;
                const dy = pos.y - this.selectedItem.y;
                this.rotationStartAngle = Math.atan2(dy, dx);
                
                // Zorg ervoor dat extra_data bestaat
                if (!this.selectedItem.extra_data) {
                    this.selectedItem.extra_data = {};
                }
                
                console.log('Started rotating item, start angle:', this.rotationStartAngle * 180 / Math.PI);
                return;
            }
        }
        
        // Voorkom normale item toevoeging als we bezig zijn met rotatie
        if (this.isRotating || this.justFinishedRotating) {
            console.log('Preventing item addition during/after rotation');
            return;
        }
        
        // --- Select line if clicked near ---
        if (!this.isDrawing && !this.isDragging) {
            const line = this.getLineAt(pos.x, pos.y);
            console.log('Checking for line at position:', line);
            if (line) {
                this.selectedItem = line.item;
                console.log('Selected line:', line.item);
                // --- Reset tekenmodus na selectie ---
                this.isDrawing = false;
                this.curvedPhase = null;
                this.curvedStart = null;
                this.curvedEnd = null;
                this.curvedControl = null;
                this.dragStart = null;
                this.dragCurrent = null;
                this.draw();
                return;
            }
        }
        
        // --- Tekenen van lijnen ---
        if (this.currentTool === 'lijn' || this.currentTool === 'curved') {
            // Check of klik op bestaande lijn of handle was
            const lineAtPos = this.getLineAt(pos.x, pos.y);
            const handleAtPos = this.selectedItem ? this.getHandleAt(pos.x, pos.y, this.selectedItem) : null;
            console.log('Line at pos:', lineAtPos, 'Handle at pos:', handleAtPos);
            
            if (lineAtPos || handleAtPos) {
                // Niet starten met tekenen
                console.log('Not starting to draw - clicked on existing line or handle');
                return;
            }
            
            // Extra check: voorkom tekenen als we net bezig waren met rotatie
            if (this.isRotating || this.justFinishedRotating) {
                console.log('Not starting to draw - rotation in progress or just finished');
                return;
            }
            
            // Start tekenen
            if (this.currentTool === 'lijn') {
                console.log('Starting to draw straight line');
                this.isDrawing = true;
                this.dragStart = pos;
                this.dragCurrent = pos;
            } else if (this.currentTool === 'curved') {
                if (!this.isDrawing) {
                    console.log('Starting curved line phase 1');
                    this.isDrawing = true;
                    this.curvedPhase = 1;
                    this.curvedStart = pos;
                    this.curvedEnd = pos;
                    this.curvedControl = null;
                } else if (this.curvedPhase === 2) {
                    console.log('Completing curved line');
                    this.curvedControl = pos;
                    this.addCurvedLine(this.curvedStart, this.curvedEnd, this.curvedControl);
                    this.isDrawing = false;
                    this.curvedPhase = null;
                    this.curvedStart = null;
                    this.curvedEnd = null;
                    this.curvedControl = null;
                    this.draw();
                }
            }
            return;
        } else if (this.currentTool && this.currentTool !== 'lijn' && this.currentTool !== 'curved') {
            // Check if clicking on existing item
            const clickedItem = this.getItemAt(pos.x, pos.y);
            if (clickedItem) {
                this.selectedItem = clickedItem;
                this.isDragging = true;
                this.dragStart = {
                    x: pos.x - clickedItem.x,
                    y: pos.y - clickedItem.y
                };
            } else {
                // Alleen item toevoegen als we niet net bezig waren met rotatie
                if (!this.justFinishedRotating) {
                    this.selectedItem = null;
                    this.addItem(pos.x, pos.y);
                } else {
                    console.log('Preventing item addition after rotation');
                    // Alleen deselecteren, geen nieuw item toevoegen
                    this.selectedItem = null;
                    this.draw();
                }
            }
        }
    }
    
    handleMouseMove(e) {
        const pos = this.getMousePos(e);
        
        // --- Rotate selected flapje/hoekflapje ---
        if (this.isRotating && this.selectedItem && (this.selectedItem.type === 'flapje' || this.selectedItem.type === 'hoekflapje')) {
            // Bereken de hoek van de muis ten opzichte van het item
            const dx = pos.x - this.selectedItem.x;
            const dy = pos.y - this.selectedItem.y;
            const currentAngle = Math.atan2(dy, dx);
            
            // Bereken het verschil met de start hoek (in radialen)
            const angleDiff = currentAngle - this.rotationStartAngle;
            
            // Haal de huidige rotatie op (in graden)
            const currentRotationDegrees = (this.selectedItem.extra_data && this.selectedItem.extra_data.rotation !== undefined) ? 
                this.selectedItem.extra_data.rotation : 0;
            
            // Bereken nieuwe rotatie (in graden)
            const newRotationDegrees = currentRotationDegrees + (angleDiff * 180 / Math.PI);
            
            console.log('Rotating:', {
                currentAngle: currentAngle * 180 / Math.PI,
                rotationStartAngle: this.rotationStartAngle * 180 / Math.PI,
                angleDiff: angleDiff * 180 / Math.PI,
                currentRotationDegrees,
                newRotationDegrees
            });
            
            // Update de rotatie
            this.selectedItem.extra_data.rotation = newRotationDegrees;
            
            // Houd rotatie binnen 0-360 graden
            while (this.selectedItem.extra_data.rotation < 0) {
                this.selectedItem.extra_data.rotation += 360;
            }
            while (this.selectedItem.extra_data.rotation >= 360) {
                this.selectedItem.extra_data.rotation -= 360;
            }
            
            // Update de start hoek voor de volgende beweging
            this.rotationStartAngle = currentAngle;
            
            this.draw();
            return;
        }
        
        // --- Drag handle for selected line ---
        if (this.isDraggingHandle && this.selectedItem && (this.selectedItem.type === 'lijn' || this.selectedItem.type === 'curved')) {
            this.moveHandle(this.selectedItem, this.draggingHandle, pos);
            this.draw();
            return;
        }
        
        if (this.isDrawing && this.currentTool === 'lijn') {
            this.dragCurrent = pos;
            this.draw(); // redraw met preview
        } else if (this.isDrawing && this.currentTool === 'curved') {
            if (this.curvedPhase === 1) {
                this.curvedEnd = pos;
                this.draw();
            } else if (this.curvedPhase === 2) {
                this.curvedControl = pos;
                this.draw();
            }
        } else if (this.isDragging && this.selectedItem) {
            this.selectedItem.x = pos.x - this.dragStart.x;
            this.selectedItem.y = pos.y - this.dragStart.y;
            this.draw();
        }
    }
    
    handleMouseUp(e) {
        if (this.isRotating && this.selectedItem) {
            this.isRotating = false;
            this.rotationStartAngle = null;
            this.saveItem(this.selectedItem);
            this.draw();
            // Zet flag om te voorkomen dat items worden toegevoegd na rotatie
            this.justFinishedRotating = true;
            // Voorkom dat er nog andere acties worden uitgevoerd na rotatie
            setTimeout(() => {
                this.isRotating = false; // Extra reset na korte vertraging
                this.justFinishedRotating = false; // Reset flag na timeout
            }, 300); // Verhoog naar 300ms voor betere stabiliteit
            return;
        }
        
        if (this.isDraggingHandle && this.selectedItem) {
            this.isDraggingHandle = false;
            this.draggingHandle = null;
            this.saveItem(this.selectedItem);
            this.draw();
            // --- Reset tekenmodus na handle drag ---
            this.isDrawing = false;
            this.curvedPhase = null;
            this.curvedStart = null;
            this.curvedEnd = null;
            this.curvedControl = null;
            this.dragStart = null;
            this.dragCurrent = null;
            return;
        }
        
        // Alleen normale item toevoeging als we niet bezig zijn met rotatie of handle drag
        if (this.isDrawing && this.currentTool === 'lijn') {
            const pos = this.getMousePos(e);
            this.addLine(this.dragStart, pos);
            this.isDrawing = false;
            this.dragStart = null;
            this.dragCurrent = null;
            this.draw();
        } else if (this.isDrawing && this.currentTool === 'curved' && this.curvedPhase === 1) {
            this.curvedPhase = 2;
            this.curvedControl = {
                x: (this.curvedStart.x + this.curvedEnd.x) / 2,
                y: (this.curvedStart.y + this.curvedEnd.y) / 2
            };
            this.draw();
        }
        
        // --- Reset tekenmodus na afronden bewerking ---
        // Reset altijd na mouse up, behalve als we nog bezig zijn met tekenen
        if (this.curvedPhase !== 2) {
            this.isDrawing = false;
            this.curvedPhase = null;
            this.curvedStart = null;
            this.curvedEnd = null;
            this.curvedControl = null;
            this.dragStart = null;
            this.dragCurrent = null;
        }
        this.isDragging = false;
    }
    
    handleMouseLeave(e) {
        if (this.isDrawing && (this.currentTool === 'lijn' || this.currentTool === 'curved')) {
            this.isDrawing = false;
            this.dragStart = null;
            this.dragCurrent = null;
            this.curvedPhase = null;
            this.curvedStart = null;
            this.curvedEnd = null;
            this.curvedControl = null;
            this.draw();
        }
        if (this.isDraggingHandle) {
            this.isDraggingHandle = false;
            this.draggingHandle = null;
            this.draw();
        }
        if (this.isRotating) {
            this.isRotating = false;
            this.rotationStartAngle = null;
            this.draw();
            // Extra reset om zeker te zijn dat rotatie volledig gestopt is
            setTimeout(() => {
                this.isRotating = false;
                this.justFinishedRotating = false;
            }, 300); // Verhoog naar 300ms voor betere stabiliteit
        }
    }
    
    handleClick(e) {
        // Alleen linker muisknop (0) mag iets toevoegen/selecteren
        if (e.button !== 0) return;
        const pos = this.getMousePos(e);
        
        if (!this.currentTool || this.currentTool === 'lijn' || this.currentTool === 'curved') {
            return;
        }
        
        // Voorkom item toevoeging als we net bezig waren met rotatie
        if (this.isRotating || this.justFinishedRotating) {
            console.log('Preventing item addition during/after rotation in handleClick');
            return;
        }
        
        // Check if clicking on existing item
        const clickedItem = this.getItemAt(pos.x, pos.y);
        if (clickedItem) {
            this.selectedItem = clickedItem;
        } else {
            // Alleen item toevoegen als we niet net bezig waren met rotatie
            if (!this.justFinishedRotating) {
                this.addItem(pos.x, pos.y);
            } else {
                console.log('Preventing item addition after rotation in handleClick');
                // Alleen deselecteren, geen nieuw item toevoegen
                this.selectedItem = null;
                this.draw();
            }
        }
    }
    
    handleContextMenu(e) {
        e.preventDefault();
        const pos = this.getMousePos(e);
        
        // Eerst checken voor normale items (spelers, ballenmand, etc.)
        let clickedItem = this.getItemAt(pos.x, pos.y);
        
        // Als geen normaal item gevonden, checken voor lijnen
        if (!clickedItem) {
            const lineResult = this.getLineAt(pos.x, pos.y);
            if (lineResult) {
                clickedItem = lineResult.item;
            }
        }
        
        if (clickedItem) {
            this.selectedItem = clickedItem;
            this.draw();
            this.showContextMenu(e, clickedItem);
        } else {
            // Deselecteer als je buiten een item klikt
            if (this.selectedItem) {
                this.selectedItem = null;
                this.draw();
            }
        }
    }
    
    showContextMenu(e, item) {
        // Remove existing context menu
        const existingMenu = document.getElementById('contextMenu');
        if (existingMenu) {
            existingMenu.remove();
        }
        
        // Get current color
        const currentColor = item.extra_data?.color || '#333';
        
        // Create context menu
        const menu = document.createElement('div');
        menu.id = 'contextMenu';
        menu.className = 'dropdown-menu show position-fixed';
        menu.style.cssText = `left: ${e.clientX}px; top: ${e.clientY}px; z-index: 9999; min-width: 250px;`;
        menu.innerHTML = `
            <div class="dropdown-header">
                <i class="fas fa-${this.getItemIcon(item.type)}"></i> ${this.getItemTypeName(item.type)}
            </div>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="#" id="deleteContextBtn">
                <i class="fas fa-trash text-danger"></i> Verwijderen
            </a>
            <a class="dropdown-item" href="#" id="duplicateContextBtn">
                <i class="fas fa-copy text-info"></i> Dupliceren
            </a>
            <div class="dropdown-divider"></div>
            <div class="dropdown-item-text">
                <strong>Kleur wijzigen:</strong>
                <div class="d-flex flex-wrap gap-1 mt-2">
                    <button type="button" class="color-option-btn btn btn-sm" style="background:#4ecdc4; border:1px solid #ccc; width:24px; height:24px;" data-color="#4ecdc4" data-item-id="${item.id}"></button>
                    <button type="button" class="color-option-btn btn btn-sm" style="background:#ff6b6b; border:1px solid #ccc; width:24px; height:24px;" data-color="#ff6b6b" data-item-id="${item.id}"></button>
                    <button type="button" class="color-option-btn btn btn-sm" style="background:#ffa500; border:1px solid #ccc; width:24px; height:24px;" data-color="#ffa500" data-item-id="${item.id}"></button>
                    <button type="button" class="color-option-btn btn btn-sm" style="background:#ffe066; border:1px solid #ccc; width:24px; height:24px;" data-color="#ffe066" data-item-id="${item.id}"></button>
                    <button type="button" class="color-option-btn btn btn-sm" style="background:#5f27cd; border:1px solid #ccc; width:24px; height:24px;" data-color="#5f27cd" data-item-id="${item.id}"></button>
                    <button type="button" class="color-option-btn btn btn-sm" style="background:#222f3e; border:1px solid #ccc; width:24px; height:24px;" data-color="#222f3e" data-item-id="${item.id}"></button>
                </div>
                <div class="mt-2">
                    <input type="color" class="form-control form-control-color" id="customColorPicker" value="${currentColor}" style="width: 100%; height: 30px;">
                </div>
            </div>
            <div class="dropdown-divider"></div>
            <div class="dropdown-item-text small text-muted">
                Positie: (${Math.round(item.x)}, ${Math.round(item.y)})
                <br>Huidige kleur: <span style="color: ${currentColor};">${currentColor}</span>
            </div>
        `;
        
        document.body.appendChild(menu);
        
        // Handle menu actions
        document.getElementById('deleteContextBtn').addEventListener('click', () => {
            this.showDeleteConfirmation(item);
            menu.remove();
        });
        
        document.getElementById('duplicateContextBtn').addEventListener('click', () => {
            this.duplicateItem(item);
            menu.remove();
        });
        
        // Handle color options
        menu.querySelectorAll('.color-option-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const color = btn.dataset.color;
                this.changeItemColor(item, color);
                menu.remove();
            });
        });
        
        // Handle custom color picker
        const customColorPicker = menu.querySelector('#customColorPicker');
        customColorPicker.addEventListener('change', () => {
            const color = customColorPicker.value;
            this.changeItemColor(item, color);
            menu.remove();
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function closeMenu() {
            menu.remove();
            document.removeEventListener('click', closeMenu);
        });
    }
    
    getItemIcon(type) {
        const icons = {
            'speler': 'user',
            'ballenmand': 'basketball-ball',
            'pion': 'circle',
            'flapje': 'square-full',
            'hoekflapje': 'object-group',
            'lijn': 'minus',
            'curved': 'wave-square'
        };
        return icons[type] || 'question';
    }
    
    duplicateItem(item) {
        const newItem = {
            ...item,
            id: this.nextId++
        };
        
        // Voor lijnen en curved lijnen: verschuif beide eindpunten
        if (item.type === 'lijn' || item.type === 'curved') {
            newItem.x = item.x + 20;
            newItem.y = item.y + 20;
            if (item.extra_data.endX) {
                newItem.extra_data.endX = item.extra_data.endX + 20;
            }
            if (item.extra_data.endY) {
                newItem.extra_data.endY = item.extra_data.endY + 20;
            }
            if (item.type === 'curved' && item.extra_data.controlX) {
                newItem.extra_data.controlX = item.extra_data.controlX + 20;
            }
            if (item.type === 'curved' && item.extra_data.controlY) {
                newItem.extra_data.controlY = item.extra_data.controlY + 20;
            }
        } else {
            // Voor normale items: alleen x en y verschuiven
            newItem.x = item.x + 20;
            newItem.y = item.y + 20;
            // Behoud rotatie voor flapje en hoekflapje
            if (item.extra_data && item.extra_data.rotation !== undefined) {
                newItem.extra_data.rotation = item.extra_data.rotation;
            }
        }
        
        this.items.push(newItem);
        this.selectedItem = newItem;
        this.draw();
        this.saveItem(newItem);
        this.showSuccessMessage('Item gedupliceerd');
    }
    
    changeItemColor(item, color) {
        // Initialize extra_data if it doesn't exist
        if (!item.extra_data) {
            item.extra_data = {};
        }
        
        // Update the color
        item.extra_data.color = color;
        
        // Redraw the canvas
        this.draw();
        
        // Save the item to server
        this.saveItem(item);
        
        // Show success message
        this.showSuccessMessage(`Kleur gewijzigd naar ${color}`);
    }
    
    handleKeyDown(e) {
        if (e.key === 'Delete' && this.selectedItem) {
            this.deleteSelected();
        } else if (e.key === 'Escape') {
            this.selectedItem = null;
            this.draw();
        }
    }
    
    addItem(x, y) {
        if (!this.currentTool || this.currentTool === 'lijn' || this.currentTool === 'curved') {
            console.log('No tool selected or tool is lijn/curved, skipping addItem');
            return;
        }
        
        // Voorkom item toevoeging tijdens rotatie
        if (this.isRotating || this.justFinishedRotating) {
            console.log('Skipping addItem because we are rotating or just finished rotating');
            return;
        }
        
        console.log('Adding item:', this.currentTool, 'at position:', x, y);
        
        const item = {
            id: this.nextId++,
            type: this.currentTool,
            x: x,
            y: y,
            rotation: 0,
            extra_data: {},
            z_index: this.items.length
        };
        // Voeg kleur toe aan speler en andere items
        item.extra_data.color = this.selectedColor;
        
        console.log('Created item:', item);
        
        this.items.push(item);
        this.selectedItem = item;
        console.log('Items after adding:', this.items);
        
        this.draw();
        this.saveItem(item);
    }
    
    addLine(start, end) {
        console.log('Adding line from', start, 'to', end);
        
        const item = {
            id: this.nextId++,
            type: 'lijn',
            x: start.x,
            y: start.y,
            rotation: 0,
            extra_data: {
                endX: end.x,
                endY: end.y,
                color: this.selectedColor
            },
            z_index: this.items.length
        };
        
        console.log('Created line item:', item);
        
        this.items.push(item);
        console.log('Items after adding line:', this.items);
        
        this.draw();
        this.saveItem(item);
    }
    
    getItemAt(x, y) {
        // Check items in reverse order (top to bottom)
        for (let i = this.items.length - 1; i >= 0; i--) {
            const item = this.items[i];
            const size = this.getItemSize(item);
            
            if (x >= item.x - size.width/2 && x <= item.x + size.width/2 &&
                y >= item.y - size.height/2 && y <= item.y + size.height/2) {
                return item;
            }
        }
        return null;
    }
    
    getItemSize(item) {
        const sizes = {
            speler: { width: 30, height: 30 },
            ballenmand: { width: 25, height: 25 },
            pion: { width: 20, height: 20 },
            flapje: { width: 15, height: 25 },
            lijn: { width: 10, height: 10 }
        };
        return sizes[item.type] || { width: 20, height: 20 };
    }
    
    deleteSelected() {
        if (this.selectedItem) {
            this.showDeleteConfirmation(this.selectedItem);
        }
    }
    
    showDeleteConfirmation(item) {
        // Create confirmation modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'deleteConfirmModal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-trash text-danger"></i> Item Verwijderen
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Weet je zeker dat je dit item wilt verwijderen?</p>
                        <div class="alert alert-info">
                            <strong>Item type:</strong> ${this.getItemTypeName(item.type)}
                            <br><strong>Positie:</strong> (${Math.round(item.x)}, ${Math.round(item.y)})
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuleren
                        </button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                            <i class="fas fa-trash"></i> Verwijderen
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        // Handle confirmation
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            this.deleteItem(item.id);
            this.selectedItem = null;
            this.draw();
            bootstrapModal.hide();
            
            // Show success message
            this.showSuccessMessage('Item succesvol verwijderd');
        });
        
        // Clean up modal after hiding
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
    
    getItemTypeName(type) {
        const typeNames = {
            'speler': 'Speler',
            'ballenmand': 'Ballenmand',
            'pion': 'Pion',
            'flapje': 'Flapje',
            'hoekflapje': 'Hoek Flapje',
            'lijn': 'Lijn',
            'curved': 'Gebogen Lijn'
        };
        return typeNames[type] || type;
    }
    
    deleteItem(id) {
        const index = this.items.findIndex(item => item.id === id);
        if (index !== -1) {
            this.items.splice(index, 1);
            this.deleteItemFromServer(id);
        }
    }
    
    clearAll() {
        if (this.items.length === 0) {
            this.showInfoMessage('Er zijn geen items om te wissen');
            return;
        }
        
        this.showClearAllConfirmation();
    }
    
    showClearAllConfirmation() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'clearAllConfirmModal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eraser text-warning"></i> Alle Items Wissen
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Let op!</strong> Deze actie kan niet ongedaan worden gemaakt.
                        </div>
                        <p>Weet je zeker dat je alle ${this.items.length} items wilt wissen?</p>
                        <div class="alert alert-info">
                            <strong>Aantal items:</strong> ${this.items.length}
                            <br><strong>Item types:</strong> ${this.getItemTypesSummary()}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuleren
                        </button>
                        <button type="button" class="btn btn-warning" id="confirmClearAllBtn">
                            <i class="fas fa-eraser"></i> Alles Wissen
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        // Handle confirmation
        document.getElementById('confirmClearAllBtn').addEventListener('click', () => {
            this.items = [];
            this.selectedItem = null;
            this.clearAllFromServer();
            this.draw();
            bootstrapModal.hide();
            
            // Show success message
            this.showSuccessMessage('Alle items succesvol gewist');
        });
        
        // Clean up modal after hiding
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
    
    getItemTypesSummary() {
        const typeCount = {};
        this.items.forEach(item => {
            typeCount[item.type] = (typeCount[item.type] || 0) + 1;
        });
        
        return Object.entries(typeCount)
            .map(([type, count]) => `${this.getItemTypeName(type)}: ${count}`)
            .join(', ');
    }
    
    showSuccessMessage(message) {
        this.showMessage(message, 'success', 'check-circle');
    }
    
    showInfoMessage(message) {
        this.showMessage(message, 'info', 'info-circle');
    }
    
    showMessage(message, type, icon) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            <i class="fas fa-${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alert);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 3000);
    }
    
    zoomIn() {
        this.zoom = Math.min(this.zoom * 1.2, 3);
        this.draw();
    }
    
    zoomOut() {
        this.zoom = Math.max(this.zoom / 1.2, 0.5);
        this.draw();
    }
    
    resetZoom() {
        this.zoom = 1;
        this.offset = { x: 0, y: 0 };
        this.draw();
    }
    
    draw() {
        console.log('Drawing canvas with', this.items.length, 'items');
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Apply zoom and offset
        this.ctx.save();
        this.ctx.translate(this.offset.x * this.zoom, this.offset.y * this.zoom);
        this.ctx.scale(this.zoom, this.zoom);
        
        // Draw court background
        this.drawCourt();
        
        // Draw items
        this.items.forEach((item, index) => {
            console.log(`Drawing item ${index}:`, item);
            this.drawItem(item);
        });
        
        // Preview rechte lijn
        if (this.isDrawing && this.currentTool === 'lijn' && this.dragStart && this.dragCurrent) {
            this.drawPreviewLine(this.dragStart, this.dragCurrent);
        }
        // Preview curved lijn
        if (this.isDrawing && this.currentTool === 'curved') {
            if (this.curvedPhase === 1 && this.curvedStart && this.curvedEnd) {
                this.drawPreviewLine(this.curvedStart, this.curvedEnd);
            } else if (this.curvedPhase === 2 && this.curvedStart && this.curvedEnd && this.curvedControl) {
                this.drawPreviewCurvedLine(this.curvedStart, this.curvedEnd, this.curvedControl);
            }
        }
        
        // Handles voor geselecteerde lijn
        if (this.selectedItem && (this.selectedItem.type === 'lijn' || this.selectedItem.type === 'curved')) {
            this.drawLineHandles(this.selectedItem);
        }
        
        // Rotatie handle voor geselecteerde flapje en hoekflapje
        if (this.selectedItem && (this.selectedItem.type === 'flapje' || this.selectedItem.type === 'hoekflapje')) {
            this.drawRotationHandle(this.selectedItem);
        }
        
        this.ctx.restore();
    }
    
    drawPreviewLine(start, end) {
        this.ctx.save();
        this.ctx.strokeStyle = this.selectedColor;
        this.ctx.lineWidth = 2;
        this.ctx.setLineDash([6, 4]);
        this.ctx.beginPath();
        this.ctx.moveTo(start.x, start.y);
        this.ctx.lineTo(end.x, end.y);
        this.ctx.stroke();
        this.ctx.setLineDash([]);
        this.ctx.restore();
    }
    
    drawPreviewCurvedLine(start, end, control) {
        this.ctx.save();
        this.ctx.strokeStyle = this.selectedColor;
        this.ctx.lineWidth = 2;
        this.ctx.setLineDash([6, 4]);
        this.ctx.beginPath();
        this.ctx.moveTo(start.x, start.y);
        this.ctx.quadraticCurveTo(control.x, control.y, end.x, end.y);
        this.ctx.stroke();
        this.ctx.setLineDash([]);
        this.ctx.restore();
    }
    
    drawCourt() {
        // Gebruik de afbeelding als achtergrond, altijd vullend
        if (this.courtImageLoaded) {
            this.ctx.drawImage(this.courtImage, 0, 0, 800, 600);
        } else {
            // Fallback: groene achtergrond
            this.ctx.fillStyle = '#90EE90';
            this.ctx.fillRect(0, 0, 800, 600);
        }
        
        // Add text labels
        this.ctx.fillStyle = '#333';
        this.ctx.font = '16px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('Padelbaan', 400, 30);
    }
    
    drawItem(item) {
        console.log('Drawing item:', item.type, 'at position:', item.x, item.y);
        
        const isSelected = this.selectedItem && this.selectedItem.id === item.id;
        
        this.ctx.save();
        this.ctx.translate(item.x, item.y);
        
        // Gebruik rotatie uit extra_data voor flapje en hoekflapje
        let rotation = 0;
        if (item.type === 'flapje' || item.type === 'hoekflapje') {
            rotation = (item.extra_data && item.extra_data.rotation !== undefined) ? item.extra_data.rotation : 0;
        } else {
            rotation = item.rotation || 0;
        }
        
        this.ctx.rotate(rotation * Math.PI / 180);
        
        switch (item.type) {
            case 'speler':
                this.drawPlayer(isSelected, item);
                break;
            case 'ballenmand':
                this.drawBasket(isSelected, item);
                break;
            case 'pion':
                this.drawCone(isSelected, item);
                break;
            case 'flapje':
                this.drawFlag(isSelected, item);
                break;
            case 'hoekflapje':
                this.drawHoekFlapje(isSelected, item);
                break;
            case 'lijn':
                this.drawLine(item, isSelected);
                break;
            case 'curved':
                this.drawCurvedLine(item, isSelected);
                break;
            default:
                console.warn('Unknown item type:', item.type);
        }
        
        // Draw selection indicator for selected items
        if (isSelected) {
            this.drawSelectionIndicator();
        }
        
        this.ctx.restore();
    }
    
    drawSelectionIndicator() {
        // Draw a pulsing selection ring around the item
        const time = Date.now() * 0.005;
        const alpha = 0.3 + 0.2 * Math.sin(time);
        
        this.ctx.save();
        this.ctx.strokeStyle = `rgba(255, 107, 107, ${alpha})`;
        this.ctx.lineWidth = 3;
        this.ctx.setLineDash([8, 4]);
        this.ctx.beginPath();
        this.ctx.arc(0, 0, 25, 0, 2 * Math.PI);
        this.ctx.stroke();
        this.ctx.restore();
    }
    
    drawRotationHandle(item) {
        const size = this.getItemSize(item);
        const handleDistance = Math.max(size.width, size.height) / 2 + 25;
        const rotation = (item.extra_data && item.extra_data.rotation !== undefined) ? item.extra_data.rotation : 0;
        
        // Bereken positie van rotatie handle (bovenkant van het item)
        // Gebruik 0 graden (bovenkant) in plaats van -90 graden (zijkant)
        const handleX = item.x + Math.cos(rotation) * handleDistance;
        const handleY = item.y + Math.sin(rotation) * handleDistance;
        
        // Teken rotatie handle
        this.ctx.save();
        this.ctx.beginPath();
        this.ctx.arc(handleX, handleY, 8, 0, 2 * Math.PI);
        this.ctx.fillStyle = '#fff';
        this.ctx.strokeStyle = '#ff0000';
        this.ctx.lineWidth = 2;
        this.ctx.fill();
        this.ctx.stroke();
        
        // Teken rotatie icoon
        this.ctx.fillStyle = '#ff0000';
        this.ctx.font = '12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText('↻', handleX, handleY);
        this.ctx.restore();
    }
    
    drawPlayer(selected, item) {
        const size = 15;
        // Gebruik kleur uit extra_data of standaard
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#4ecdc4';
        // Player circle
        this.ctx.fillStyle = color;
        this.ctx.beginPath();
        this.ctx.arc(0, 0, size, 0, 2 * Math.PI);
        this.ctx.fill();
        // Player outline
        this.ctx.strokeStyle = selected ? '#ff0000' : '#333';
        this.ctx.lineWidth = selected ? 3 : 2;
        this.ctx.stroke();
        // Player icon
        this.ctx.fillStyle = '#fff';
        this.ctx.font = '12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('👤', 0, 4);
    }
    
    drawBasket(selected, item) {
        const size = 12;
        // Gebruik kleur uit extra_data of standaard
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#ffa500';
        
        // Basket
        this.ctx.fillStyle = color;
        this.ctx.fillRect(-size, -size, size * 2, size * 2);
        
        // Basket outline
        this.ctx.strokeStyle = selected ? '#ff0000' : '#333';
        this.ctx.lineWidth = selected ? 3 : 2;
        this.ctx.stroke();
        
        // Ball icon
        this.ctx.fillStyle = '#fff';
        this.ctx.font = '10px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('⚽', 0, 2);
    }
    
    drawCone(selected, item) {
        const size = 10;
        // Gebruik kleur uit extra_data of standaard
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#ffd700';
        
        // Cone
        this.ctx.fillStyle = color;
        this.ctx.beginPath();
        this.ctx.moveTo(-size, size);
        this.ctx.lineTo(0, -size);
        this.ctx.lineTo(size, size);
        this.ctx.closePath();
        this.ctx.fill();
        
        // Cone outline
        this.ctx.strokeStyle = selected ? '#ff0000' : '#333';
        this.ctx.lineWidth = selected ? 3 : 2;
        this.ctx.stroke();
    }
    
    drawFlag(selected, item) {
        // Compacte rechthoek (vergelijkbaar met speler/pion)
        const width = 30;
        const height = 12;
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#ffe600';
        
        this.ctx.fillStyle = color;
        this.ctx.fillRect(-width/2, -height/2, width, height);
        this.ctx.strokeStyle = selected ? '#ff0000' : '#333';
        this.ctx.lineWidth = selected ? 3 : 2;
        this.ctx.strokeRect(-width/2, -height/2, width, height);
    }

    drawHoekFlapje(selected, item) {
        // Echte hoek: verticale en horizontale balk vormen samen een L
        const leg = 30;
        const thick = 12;
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#ffe600';
        
        this.ctx.fillStyle = color;
        // Verticale deel (links)
        this.ctx.fillRect(-leg/2, -leg/2, thick, leg);
        // Horizontale deel (boven, start direct aan rechterkant van verticale balk)
        this.ctx.fillRect(-leg/2 + thick, -leg/2, leg - thick, thick);
        this.ctx.strokeStyle = selected ? '#ff0000' : '#333';
        this.ctx.lineWidth = selected ? 3 : 2;
        // Omranding verticaal
        this.ctx.strokeRect(-leg/2, -leg/2, thick, leg);
        // Omranding horizontaal
        this.ctx.strokeRect(-leg/2 + thick, -leg/2, leg - thick, thick);
    }
    
    drawLine(item, selected) {
        const endX = item.extra_data.endX || item.x + 50;
        const endY = item.extra_data.endY || item.y;
        // Gebruik kleur uit extra_data of standaard
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#333';
        this.ctx.strokeStyle = color;
        this.ctx.lineWidth = selected ? 4 : 2;
        this.ctx.beginPath();
        this.ctx.moveTo(0, 0);
        this.ctx.lineTo(endX - item.x, endY - item.y);
        this.ctx.stroke();
    }
    
    drawCurvedLine(item, selected) {
        const endX = item.extra_data.endX || item.x + 50;
        const endY = item.extra_data.endY || item.y;
        const controlX = item.extra_data.controlX || (item.x + endX) / 2;
        const controlY = item.extra_data.controlY || (item.y + endY) / 2;
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#333';
        this.ctx.strokeStyle = color;
        this.ctx.lineWidth = selected ? 4 : 2;
        this.ctx.beginPath();
        this.ctx.moveTo(0, 0);
        this.ctx.quadraticCurveTo(controlX - item.x, controlY - item.y, endX - item.x, endY - item.y);
        this.ctx.stroke();
    }
    
    loadExistingItems() {
        console.log('Loading existing items...');
        console.log('lesData:', window.lesData);
        
        // Check if lesData is available
        if (!window.lesData) {
            console.error('lesData is not available. Make sure the page is loaded correctly.');
            this.items = [];
            return;
        }
        
        if (window.lesData.items) {
            console.log('Found items in lesData:', window.lesData.items);
            
            this.items = window.lesData.items.map(item => {
                console.log('Processing item:', item);
                
                // Parse extra_data als het een string is
                let extraData = item.extra_data;
                if (typeof extraData === 'string') {
                    try {
                        extraData = JSON.parse(extraData);
                        console.log('Parsed extra_data:', extraData);
                    } catch (e) {
                        console.warn('Could not parse extra_data:', extraData);
                        extraData = {};
                    }
                }
                // Forceer numerieke waarden voor coördinaten
                const processedItem = {
                    ...item,
                    x: Number(item.x),
                    y: Number(item.y),
                    extra_data: {
                        ...extraData,
                        endX: extraData.endX !== undefined ? Number(extraData.endX) : undefined,
                        endY: extraData.endY !== undefined ? Number(extraData.endY) : undefined,
                        controlX: extraData.controlX !== undefined ? Number(extraData.controlX) : undefined,
                        controlY: extraData.controlY !== undefined ? Number(extraData.controlY) : undefined,
                        color: extraData.color,
                        rotation: extraData.rotation !== undefined ? Number(extraData.rotation) : undefined
                    },
                    id: item.id || this.nextId++
                };
                
                // Speciale debug voor lijnen
                if (item.type === 'lijn') {
                    console.log('=== REchte lijn debug ===');
                    console.log('Original item:', item);
                    console.log('Processed item:', processedItem);
                    console.log('extra_data:', processedItem.extra_data);
                    console.log('endX:', processedItem.extra_data.endX);
                    console.log('endY:', processedItem.extra_data.endY);
                    console.log('color:', processedItem.extra_data.color);
                    console.log('=======================');
                }
                
                console.log('Processed item:', processedItem);
                return processedItem;
            });
            
            this.nextId = Math.max(...this.items.map(item => item.id), 0) + 1;
            console.log('Loaded items:', this.items);
            console.log('Next ID:', this.nextId);
            console.log('Items array length:', this.items.length);
        } else {
            console.log('No items found in lesData');
            this.items = [];
        }
    }
    
    async saveItem(item) {
        try {
            console.log('Saving item:', item);
            
            // Check if lesData is available
            if (!window.lesData || !window.lesData.id) {
                throw new Error('Les data niet beschikbaar. Probeer de pagina te herladen.');
            }
            
            const response = await fetch('api/les_item_toevoegen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    les_id: window.lesData.id,
                    item: item,
                    csrf_token: document.querySelector('meta[name="csrf-token"]').content
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Save response:', data);
            
            if (!data.success) {
                console.error('Error saving item:', data.error);
                alert('Fout bij opslaan: ' + data.error);
            } else {
                console.log('Item saved successfully');
            }
        } catch (error) {
            console.error('Error saving item:', error);
            alert('Fout bij opslaan: ' + error.message);
        }
    }
    
    async deleteItemFromServer(id) {
        try {
            // Check if lesData is available
            if (!window.lesData || !window.lesData.id) {
                throw new Error('Les data niet beschikbaar. Probeer de pagina te herladen.');
            }
            
            const response = await fetch('api/les_item_verwijderen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: id,
                    csrf_token: document.querySelector('meta[name="csrf-token"]').content
                })
            });
            
            const data = await response.json();
            if (!data.success) {
                console.error('Error deleting item:', data.error);
            }
        } catch (error) {
            console.error('Error deleting item:', error);
        }
    }
    
    async clearAllFromServer() {
        try {
            // Check if lesData is available
            if (!window.lesData || !window.lesData.id) {
                throw new Error('Les data niet beschikbaar. Probeer de pagina te herladen.');
            }
            
            const response = await fetch('api/les_items_wissen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    les_id: window.lesData.id,
                    csrf_token: document.querySelector('meta[name="csrf-token"]').content
                })
            });
            
            const data = await response.json();
            if (!data.success) {
                console.error('Error clearing items:', data.error);
            }
        } catch (error) {
            console.error('Error clearing items:', error);
        }
    }
    
    async save() {
        const saveBtn = document.getElementById('editorSaveBtn');
        const originalText = saveBtn.innerHTML;
        
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Opslaan...';
        saveBtn.disabled = true;
        
        try {
            // Check if lesData is available
            if (!window.lesData || !window.lesData.id) {
                throw new Error('Les data niet beschikbaar. Probeer de pagina te herladen.');
            }
            
            const response = await fetch('api/les_opslaan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    les_id: window.lesData.id,
                    items: this.items,
                    csrf_token: document.querySelector('meta[name="csrf-token"]').content
                })
            });
            
            const data = await response.json();
            if (data.success) {
                // Show success modal
                const modal = new bootstrap.Modal(document.getElementById('saveModal'));
                modal.show();
            } else {
                alert('Fout bij opslaan: ' + data.error);
            }
        } catch (error) {
            console.error('Error saving:', error);
            alert('Er is een fout opgetreden bij het opslaan.');
        } finally {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    }

    addCurvedLine(start, end, control) {
        const item = {
            id: this.nextId++,
            type: 'curved',
            x: start.x,
            y: start.y,
            rotation: 0,
            extra_data: {
                endX: end.x,
                endY: end.y,
                controlX: control.x,
                controlY: control.y,
                color: this.selectedColor
            },
            z_index: this.items.length
        };
        this.items.push(item);
        this.draw();
        this.saveItem(item);
    }

    // --- Selectie en handles helpers ---
    getLineAt(x, y) {
        // Rechte lijnen: check afstand tot lijnsegment
        for (let i = this.items.length - 1; i >= 0; i--) {
            const item = this.items[i];
            if (item.type === 'lijn') {
                const ex = item.extra_data.endX || item.x + 50;
                const ey = item.extra_data.endY || item.y;
                console.log('Checking straight line:', item.id, 'from', item.x, item.y, 'to', ex, ey);
                if (this.pointNearLine(x, y, item.x, item.y, ex, ey, 8)) {
                    console.log('Found straight line at position!');
                    return {item, type: 'lijn'};
                }
            } else if (item.type === 'curved') {
                const ex = item.extra_data.endX || item.x + 50;
                const ey = item.extra_data.endY || item.y;
                const cx = item.extra_data.controlX || (item.x + ex) / 2;
                const cy = item.extra_data.controlY || (item.y + ey) / 2;
                if (this.pointNearQuadratic(x, y, item.x, item.y, cx, cy, ex, ey, 8)) {
                    return {item, type: 'curved'};
                }
            }
        }
        return null;
    }
    pointNearLine(px, py, x1, y1, x2, y2, threshold) {
        // Projectie van punt op lijnsegment
        const dx = x2 - x1;
        const dy = y2 - y1;
        const length2 = dx*dx + dy*dy;
        
        console.log('pointNearLine debug:');
        console.log('  Point:', px, py);
        console.log('  Line from:', x1, y1, 'to:', x2, y2);
        console.log('  dx, dy:', dx, dy);
        console.log('  length2:', length2);
        
        if (length2 === 0) {
            console.log('  Line has zero length, returning false');
            return false;
        }
        
        let t = ((px - x1) * dx + (py - y1) * dy) / length2;
        t = Math.max(0, Math.min(1, t));
        
        console.log('  t value:', t);
        
        // Bereken geprojecteerde punt
        const projectedX = x1 + t * dx;
        const projectedY = y1 + t * dy;
        
        console.log('  Projected X:', projectedX);
        console.log('  Projected Y:', projectedY);
        
        // Bereken afstand
        const dx2 = px - projectedX;
        const dy2 = py - projectedY;
        const distance = Math.sqrt(dx2 * dx2 + dy2 * dy2);
        
        console.log('  dx2, dy2:', dx2, dy2);
        console.log('  Distance:', distance, 'Threshold:', threshold);
        console.log('  Result:', distance <= threshold);
        
        return distance <= threshold;
    }
    pointNearQuadratic(px, py, x1, y1, cx, cy, x2, y2, threshold) {
        // Sample de curve op 20 punten
        let minDist = Infinity;
        for (let t = 0; t <= 1; t += 0.05) {
            const xt = (1-t)*(1-t)*x1 + 2*(1-t)*t*cx + t*t*x2;
            const yt = (1-t)*(1-t)*y1 + 2*(1-t)*t*cy + t*t*y2;
            const dist = Math.hypot(px - xt, py - yt);
            if (dist < minDist) minDist = dist;
        }
        return minDist <= threshold;
    }
    getHandleAt(x, y, item) {
        const handles = this.getLineHandles(item);
        for (const h of handles) {
            if (Math.hypot(x - h.x, y - h.y) < 12) return h.name;
        }
        return null;
    }
    
    getRotationHandleAt(x, y, item) {
        if (item.type !== 'flapje' && item.type !== 'hoekflapje') return null;
        
        const size = this.getItemSize(item);
        const handleDistance = Math.max(size.width, size.height) / 2 + 25;
        const rotation = (item.extra_data && item.extra_data.rotation !== undefined) ? item.extra_data.rotation : 0;
        
        // Bereken positie van rotatie handle (bovenkant van het item)
        // Gebruik 0 graden (bovenkant) in plaats van -90 graden (zijkant)
        const handleX = item.x + Math.cos(rotation) * handleDistance;
        const handleY = item.y + Math.sin(rotation) * handleDistance;
        
        // Check of klik binnen bereik van handle is
        if (Math.hypot(x - handleX, y - handleY) < 12) {
            return 'rotation';
        }
        
        return null;
    }
    
    calculateRotationAngle(x, y, item) {
        const dx = x - item.x;
        const dy = y - item.y;
        return Math.atan2(dy, dx);
    }
    
    updateItemRotation(item, newAngle) {
        // Initialize extra_data if it doesn't exist
        if (!item.extra_data) {
            item.extra_data = {};
        }
        
        // Update the rotation (convert to degrees)
        item.extra_data.rotation = newAngle * 180 / Math.PI;
        
        // Keep rotation between 0 and 360 degrees
        while (item.extra_data.rotation < 0) {
            item.extra_data.rotation += 360;
        }
        while (item.extra_data.rotation >= 360) {
            item.extra_data.rotation -= 360;
        }
    }
    getLineHandles(item) {
        if (item.type === 'lijn') {
            const ex = item.extra_data.endX || item.x + 50;
            const ey = item.extra_data.endY || item.y;
            return [
                {name: 'start', x: item.x, y: item.y},
                {name: 'end', x: ex, y: ey}
            ];
        } else if (item.type === 'curved') {
            const ex = item.extra_data.endX || item.x + 50;
            const ey = item.extra_data.endY || item.y;
            const cx = item.extra_data.controlX || (item.x + ex) / 2;
            const cy = item.extra_data.controlY || (item.y + ey) / 2;
            return [
                {name: 'start', x: item.x, y: item.y},
                {name: 'end', x: ex, y: ey},
                {name: 'control', x: cx, y: cy}
            ];
        }
        return [];
    }
    drawLineHandles(item) {
        const handles = this.getLineHandles(item);
        for (const h of handles) {
            this.ctx.save();
            this.ctx.beginPath();
            this.ctx.arc(h.x, h.y, 8, 0, 2 * Math.PI);
            this.ctx.fillStyle = '#fff';
            this.ctx.strokeStyle = '#ff0000';
            this.ctx.lineWidth = 2;
            this.ctx.fill();
            this.ctx.stroke();
            this.ctx.restore();
        }
    }
    moveHandle(item, handle, pos) {
        if (item.type === 'lijn') {
            if (handle === 'start') {
                item.x = pos.x;
                item.y = pos.y;
            } else if (handle === 'end') {
                item.extra_data.endX = pos.x;
                item.extra_data.endY = pos.y;
            }
        } else if (item.type === 'curved') {
            if (handle === 'start') {
                item.x = pos.x;
                item.y = pos.y;
            } else if (handle === 'end') {
                item.extra_data.endX = pos.x;
                item.extra_data.endY = pos.y;
            } else if (handle === 'control') {
                item.extra_data.controlX = pos.x;
                item.extra_data.controlY = pos.y;
            }
        }
    }
}

// Beschrijving form handler
class DescriptionFormHandler {
    constructor() {
        this.form = document.getElementById('descriptionForm');
        this.submitBtn = this.form.querySelector('button[type="submit"]');
        this.init();
    }
    
    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const originalText = this.submitBtn.innerHTML;
        this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opslaan...';
        this.submitBtn.disabled = true;
        
        try {
            // Eerst beschrijving opslaan
            const formData = new FormData(this.form);
            formData.append('les_id', window.lesData.id);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            
            const response = await fetch('api/les_beschrijving_opslaan.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Dan les instellingen opslaan
                await this.saveLessonSettings();
                this.showSuccessMessage('Beschrijving en instellingen succesvol opgeslagen!');
            } else {
                this.showErrorMessage('Fout bij opslaan: ' + data.error);
            }
        } catch (error) {
            console.error('Error saving description:', error);
            this.showErrorMessage('Er is een fout opgetreden bij het opslaan.');
        } finally {
            this.submitBtn.innerHTML = originalText;
            this.submitBtn.disabled = false;
        }
    }
    
    async saveLessonSettings() {
        try {
            const lesId = window.lesData.id;
            const visibility = document.querySelector('input[name="visibility"]:checked')?.value || 'public';
            const clubId = document.getElementById('club_id')?.value || '';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            const formData = new FormData();
            formData.append('les_id', lesId);
            formData.append('visibility', visibility);
            formData.append('club_id', clubId);
            formData.append('csrf_token', csrfToken);
            
            const response = await fetch('api/les_instellingen_opslaan.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                console.error('Error saving lesson settings:', data.error);
            }
        } catch (error) {
            console.error('Error saving lesson settings:', error);
        }
    }
    
    showSuccessMessage(message) {
        // Create success alert
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show';
        alert.innerHTML = `
            <i class="fas fa-check-circle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert before the form
        this.form.parentNode.insertBefore(alert, this.form);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 3000);
    }
    
    showErrorMessage(message) {
        // Create error alert
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert before the form
        this.form.parentNode.insertBefore(alert, this.form);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

// Initialize editor when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for lesData...');
    console.log('window.lesData:', window.lesData);
    
    if (!window.lesData) {
        console.error('lesData is not available on DOMContentLoaded');
        alert('Fout: Les data niet beschikbaar. Probeer de pagina te herladen.');
        return;
    }
    
    new PadelEditor();
    
    // Initialize description form handler
    if (document.getElementById('descriptionForm')) {
        new DescriptionFormHandler();
    }
}); 