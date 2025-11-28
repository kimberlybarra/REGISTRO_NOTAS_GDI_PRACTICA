<?php
session_start();
if(isset($_SESSION['docente_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institución Nacional - Sistema de Registro de Notas</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="presentacion">
    <div class="presentacion-container">
        <header class="presentacion-header">
        <div class="logo-container">
            <h1>Institución Nacional</h1>
        </div>

        </header>
        
        <main class="presentacion-main">
            <div class="hero-section">
                <h2>Sistema Integral de Gestión Académica</h2>
                <p>Herramienta especializada para el registro, seguimiento y análisis del rendimiento estudiantil de manera eficiente y organizada</p>
                
                <div class="hero-features">
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <h3>Seguimiento Académico Detallado</h3>
                        <p>Monitorea el progreso individual y grupal de cada estudiante con reportes visuales</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <h3>Gestión Integral de Estudiantes</h3>
                        <p>Administra toda la información académica y personal de tus alumnos en un solo lugar</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-file-export"></i>
                        <h3>Reportes Automatizados</h3>
                        <p>Genera reportes detallados en formatos Excel y PDF con un solo clic</p>
                    </div>
                </div>
                
                <a href="login.php" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Acceder al Sistema
                </a>
            </div>
        </main>
        
        <footer class="presentacion-footer">
            <p>&copy; 2025 Institución Nacional. Todos los derechos reservados.</p>
        </footer>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>