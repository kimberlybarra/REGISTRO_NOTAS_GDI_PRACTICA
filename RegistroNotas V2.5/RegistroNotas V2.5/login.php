<?php
session_start();
include 'includes/database.php';

if(isset($_SESSION['docente_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    
    $stmt = $pdo->prepare("SELECT * FROM docente WHERE DNIdocente = ?");
    $stmt->execute([$usuario]);
    $docente = $stmt->fetch();
    
    if($docente && password_verify($contrasena, $docente['contrasena'])) {
        $_SESSION['docente_id'] = $docente['DNIdocente'];
        $_SESSION['docente_nombres'] = $docente['nombres'];
        $_SESSION['docente_apellidos'] = $docente['apellidos'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Institución Nacional</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <!-- Partículas decorativas -->
    <div class="particles" id="particles"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="img/logo.png" alt="Logo Institución Nacional" class="login-logo">
                <h2>Acceso al Sistema</h2>
                <p>Plataforma de Gestión Académica - Institución Nacional</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="usuario">
                        <i class="fas fa-user"></i>
                        Usuario
                    </label>
                    <input type="text" id="usuario" name="usuario" required placeholder="Ingrese su usuario">
                </div>
                
                <div class="form-group">
                    <label for="contrasena">
                        <i class="fas fa-lock"></i>
                        Contraseña
                    </label>
                    <input type="password" id="contrasena" name="contrasena" required placeholder="Ingrese su contraseña">
                    <i class="fas fa-key"></i>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        Recordar sesión
                    </label>
                    <a href="#" class="forgot-password">
                        <i class="fas fa-question-circle"></i>
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="login-footer">
                <p>Institución Nacional</p>
                <small>Sistema de Gestión Académica © 2025</small>
            </div>
            <!-- Datos de acceso de demostración -->
            <div class="demo-access">
                <div class="demo-header">
                    <i class="fas fa-info-circle"></i>
                    <span>Datos de acceso de prueba</span>
                </div>
                <div class="demo-credentials">
                    <div class="credential-item">
                        <span class="credential-label">Usuario:</span>
                        <span class="credential-value">demo</span>
                        <i class="fas fa-copy copy-btn" data-text="demo"></i>
                    </div>
                    <div class="credential-item">
                        <span class="credential-label">Contraseña:</span>
                        <span class="credential-value">password</span>
                        <i class="fas fa-copy copy-btn" data-text="password"></i>
                    </div>
                </div>
                <div class="demo-note">
                    <i class="fas fa-exclamation-triangle"></i>
                    Usa estos datos para probar el sistema
                </div>
            </div>
        </div>
    </div>
    
<script>
    // Efecto de partículas decorativas (tu código existente)
    document.addEventListener('DOMContentLoaded', function() {
        const particlesContainer = document.getElementById('particles');
        const particleCount = 15;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            
            const left = Math.random() * 100;
            const top = Math.random() * 100;
            const size = Math.random() * 6 + 2;
            const delay = Math.random() * 5;
            
            particle.style.cssText = `
                left: ${left}%;
                top: ${top}%;
                width: ${size}px;
                height: ${size}px;
                animation-delay: ${delay}s;
                opacity: ${Math.random() * 0.3 + 0.1};
            `;
            
            particlesContainer.appendChild(particle);
        }
        
        // Efecto de focus mejorado
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
        
        // Funcionalidad de copiar credenciales
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-text');
                navigator.clipboard.writeText(text).then(() => {
                    const originalIcon = this.className;
                    this.className = 'fas fa-check copied';
                    setTimeout(() => {
                        this.className = originalIcon;
                    }, 2000);
                });
            });
        });
    });
</script>
</body>
</html>