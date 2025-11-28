// Toggle del menú lateral en dispositivos móviles
document.addEventListener('DOMContentLoaded', function() {
    






    // Función para exportar tabla a Excel (alternativa)
function exportTableToExcel(tableId, filename = '') {
    const table = document.getElementById(tableId);
    const html = table.outerHTML;
    
    // Crear blob y descargar
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    
    // Crear enlace de descarga
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'reporte_notas.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
}

// Función para generar reporte PDF (usando print)
function generatePDF() {
    window.print();
}

// Mejoras en la tabla de reportes
function initReportTable() {
    const table = document.getElementById('notas-table');
    if (!table) return;
    
    // Agregar hover effects
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Agregar tooltips para notas
    const notaBadges = table.querySelectorAll('.nota-badge');
    notaBadges.forEach(badge => {
        const nota = badge.textContent.trim();
        if (nota && nota !== '--') {
            let significado = '';
            switch(nota) {
                case 'AD': significado = 'Logro Destacado'; break;
                case 'A': significado = 'Logro Esperado'; break;
                case 'B': significado = 'En Proceso'; break;
                case 'C': significado = 'En Inicio'; break;
            }
            badge.title = significado;
        }
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initReportTable();
});
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if(menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Animaciones para elementos al hacer scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.stat-card, .course-card, .activity-item');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementBottom = element.getBoundingClientRect().bottom;
            const isVisible = (elementTop < window.innerHeight - 100) && (elementBottom > 0);
            
            if(isVisible) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Inicializar elementos con opacidad 0 para la animación
    const animatedElements = document.querySelectorAll('.stat-card, .course-card, .activity-item');
    animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    
    // Ejecutar animación al cargar y al hacer scroll
    window.addEventListener('load', animateOnScroll);
    window.addEventListener('scroll', animateOnScroll);
    
    // Validación de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if(!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#28a745';
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
            }
        });
    });
    
    // Efectos hover mejorados para tarjetas
    const cards = document.querySelectorAll('.stat-card, .course-card, .feature');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});