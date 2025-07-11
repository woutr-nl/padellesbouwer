/**
 * Padel Les Viewer
 * Read-only viewer voor padel-lessen
 */

class PadelViewer {
    constructor() {
        this.canvas = document.getElementById('padelCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.zoom = 1;
        this.offset = { x: 0, y: 0 };
        this.items = [];
        
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
            console.error('lesData is not available. Viewer cannot be initialized.');
            alert('Fout: Les data niet beschikbaar. Probeer de pagina te herladen.');
            return;
        }
        
        console.log('Initializing viewer with lesData:', window.lesData);
        
        this.setupCanvas();
        this.setupEventListeners();
        this.loadItems();
        this.draw();
    }
    
    setupCanvas() {
        // Geen dynamische resizing meer
    }
    
    setupEventListeners() {
        // Zoom controls
        document.getElementById('zoomInBtn').addEventListener('click', () => this.zoomIn());
        document.getElementById('zoomOutBtn').addEventListener('click', () => this.zoomOut());
        document.getElementById('resetZoomBtn').addEventListener('click', () => this.resetZoom());
    }
    
    loadItems() {
        console.log('Loading items for viewer...');
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
                    }
                };
                
                console.log('Processed item:', processedItem);
                console.log('Item rotation:', processedItem.extra_data.rotation);
                return processedItem;
            });
            
            console.log('Loaded items:', this.items);
            console.log('Items array length:', this.items.length);
        } else {
            console.log('No items found in lesData');
            this.items = [];
        }
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
        // Alleen label
        this.ctx.fillStyle = '#333';
        this.ctx.font = '16px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('Padelbaan', 400, 30);
    }
    
    drawItem(item) {
        console.log('Drawing item:', item.type, 'at position:', item.x, item.y);
        
        this.ctx.save();
        this.ctx.translate(item.x, item.y);
        
        // Gebruik rotatie uit extra_data voor flapje en hoekflapje
        let rotation = 0;
        if (item.type === 'flapje' || item.type === 'hoekflapje') {
            rotation = (item.extra_data && item.extra_data.rotation !== undefined) ? item.extra_data.rotation : 0;
            console.log(`Applying rotation for ${item.type}:`, rotation);
        } else {
            rotation = item.rotation || 0;
        }
        
        this.ctx.rotate(rotation * Math.PI / 180);
        
        switch (item.type) {
            case 'speler':
                this.drawPlayer(item);
                break;
            case 'ballenmand':
                this.drawBasket(item);
                break;
            case 'pion':
                this.drawCone(item);
                break;
            case 'flapje':
                this.drawFlag(item);
                break;
            case 'hoekflapje':
                this.drawHoekFlapje(item);
                break;
            case 'lijn':
                this.drawLine(item);
                break;
            case 'curved':
                this.drawCurvedLine(item);
                break;
            default:
                console.warn('Unknown item type:', item.type);
        }
        
        this.ctx.restore();
    }
    
    drawCurvedLine(item) {
        const endX = item.extra_data.endX || item.x + 50;
        const endY = item.extra_data.endY || item.y;
        const controlX = item.extra_data.controlX || (item.x + endX) / 2;
        const controlY = item.extra_data.controlY || (item.y + endY) / 2;
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#333';
        this.ctx.strokeStyle = color;
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.moveTo(0, 0);
        this.ctx.quadraticCurveTo(controlX - item.x, controlY - item.y, endX - item.x, endY - item.y);
        this.ctx.stroke();
    }
    
    drawPlayer(item) {
        const size = 15;
        // Gebruik kleur uit extra_data of standaard
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#4ecdc4';
        // Player circle
        this.ctx.fillStyle = color;
        this.ctx.beginPath();
        this.ctx.arc(0, 0, size, 0, 2 * Math.PI);
        this.ctx.fill();
        // Player outline
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 2;
        this.ctx.stroke();
        // Player icon
        this.ctx.fillStyle = '#fff';
        this.ctx.font = '12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('👤', 0, 4);
    }
    
    drawBasket(item) {
        const size = 15; // 30x30 mand
        this.ctx.save();
        this.ctx.lineJoin = 'round';
        this.ctx.lineCap = 'round';
        // Mand raster (onder alles)
        this.ctx.strokeStyle = '#222';
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.moveTo(-size+3, -size+5); this.ctx.lineTo(-size+3, size-5); // links
        this.ctx.moveTo(size-3, -size+5); this.ctx.lineTo(size-3, size-5);   // rechts
        this.ctx.moveTo(-size+5, -size+3); this.ctx.lineTo(size-5, -size+3); // boven
        this.ctx.moveTo(-size+5, size-3); this.ctx.lineTo(size-5, size-3);   // onder
        this.ctx.stroke();
        // Rasterlijnen verticaal
        for(let i=1;i<4;i++){
            let x = -size+5 + i*7;
            this.ctx.beginPath();
            this.ctx.moveTo(x, -size+5); this.ctx.lineTo(x, size-5);
            this.ctx.stroke();
        }
        // Rasterlijnen horizontaal
        for(let i=1;i<4;i++){
            let y = -size+5 + i*7;
            this.ctx.beginPath();
            this.ctx.moveTo(-size+5, y); this.ctx.lineTo(size-5, y);
            this.ctx.stroke();
        }
        // Ballen (kleiner, overlappend, 4x4 grid, meer naar binnen)
        const ballRadius = 4;
        const positions = [-7, -2.5, 2.5, 7];
        for (let ix = 0; ix < positions.length; ix++) {
            for (let iy = 0; iy < positions.length; iy++) {
                const cx = positions[ix];
                const cy = positions[iy];
                this.ctx.beginPath();
                this.ctx.arc(cx, cy, ballRadius, 0, 2 * Math.PI);
                this.ctx.fillStyle = '#ffe600';
                this.ctx.shadowColor = '#bbb';
                this.ctx.shadowBlur = 1.5;
                this.ctx.fill();
                this.ctx.shadowBlur = 0;
                // Tennisbal curve
                this.ctx.strokeStyle = '#fff';
                this.ctx.lineWidth = 0.9;
                this.ctx.beginPath();
                this.ctx.arc(cx-1, cy, ballRadius-1, 0.7, 2.4);
                this.ctx.stroke();
                this.ctx.beginPath();
                this.ctx.arc(cx+1, cy, ballRadius-1, 3.2, 5.0);
                this.ctx.stroke();
            }
        }
        // Mand outline (bovenop alles)
        this.ctx.strokeStyle = '#111';
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.moveTo(-size+4, -size+4);
        this.ctx.lineTo(size-4, -size+4);
        this.ctx.lineTo(size-4, size-4);
        this.ctx.lineTo(-size+4, size-4);
        this.ctx.closePath();
        this.ctx.stroke();
        this.ctx.restore();
    }
    
    drawCone(item) {
        const size = 10;
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#ffd700';
        this.ctx.fillStyle = color;
        this.ctx.beginPath();
        this.ctx.moveTo(-size, size);
        this.ctx.lineTo(0, -size);
        this.ctx.lineTo(size, size);
        this.ctx.closePath();
        this.ctx.fill();
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 2;
        this.ctx.stroke();
    }
    
    drawFlag(item) {
        // Compacte rechthoek (30x12px, kleur uit extra_data)
        const width = 30;
        const height = 12;
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#ffe600';
        this.ctx.fillStyle = color;
        this.ctx.fillRect(-width/2, -height/2, width, height);
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 2;
        this.ctx.strokeRect(-width/2, -height/2, width, height);
    }
    
    drawHoekFlapje(item) {
        // L-vorm, benen 30px, dikte 12px, kleur uit extra_data
        const leg = 30;
        const thick = 12;
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#ffe600';
        this.ctx.fillStyle = color;
        // Verticaal deel
        this.ctx.fillRect(-leg/2, -leg/2, thick, leg);
        // Horizontaal deel
        this.ctx.fillRect(-leg/2 + thick, -leg/2, leg - thick, thick);
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 2;
        this.ctx.strokeRect(-leg/2, -leg/2, thick, leg);
        this.ctx.strokeRect(-leg/2 + thick, -leg/2, leg - thick, thick);
    }
    
    drawLine(item) {
        const endX = item.extra_data.endX || item.x + 50;
        const endY = item.extra_data.endY || item.y;
        // Gebruik kleur uit extra_data of standaard
        const color = (item && item.extra_data && item.extra_data.color) ? item.extra_data.color : '#333';
        this.ctx.strokeStyle = color;
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.moveTo(0, 0);
        this.ctx.lineTo(endX - item.x, endY - item.y);
        this.ctx.stroke();
    }
}

// Initialize viewer when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for lesData...');
    console.log('window.lesData:', window.lesData);
    
    if (!window.lesData) {
        console.error('lesData is not available on DOMContentLoaded');
        alert('Fout: Les data niet beschikbaar. Probeer de pagina te herladen.');
        return;
    }
    
    new PadelViewer();
}); 