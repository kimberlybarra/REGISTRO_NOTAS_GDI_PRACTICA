<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

// Obtener información del docente
$stmt = $pdo->prepare("SELECT d.*, GROUP_CONCAT(td.telefono SEPARATOR ', ') as telefonos 
                      FROM docente d 
                      LEFT JOIN telefono_docente td ON d.DNIdocente = td.DNIdocente 
                      WHERE d.DNIdocente = ? 
                      GROUP BY d.DNIdocente");
$stmt->execute([$_SESSION['docente_id']]);
$docente = $stmt->fetch();

$mensaje = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $contrasena_actual = $_POST['contrasena_actual'];
    $nueva_contrasena = $_POST['nueva_contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    
    // Actualizar información básica
    $stmt = $pdo->prepare("UPDATE docente SET nombres = ?, apellidos = ?, correo = ? WHERE DNIdocente = ?");
    $stmt->execute([$nombres, $apellidos, $correo, $_SESSION['docente_id']]);
    
    // Actualizar teléfono
    if($telefono) {
        // Eliminar teléfonos existentes
        $stmt = $pdo->prepare("DELETE FROM telefono_docente WHERE DNIdocente = ?");
        $stmt->execute([$_SESSION['docente_id']]);
        
        // Insertar nuevo teléfono
        $stmt = $pdo->prepare("INSERT INTO telefono_docente (DNIdocente, telefono) VALUES (?, ?)");
        $stmt->execute([$_SESSION['docente_id'], $telefono]);
    }
    
    // Cambiar contraseña si se proporciona
    if($nueva_contrasena && $contrasena_actual) {
        if(password_verify($contrasena_actual, $docente['contrasena'])) {
            if($nueva_contrasena === $confirmar_contrasena) {
                $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE docente SET contrasena = ? WHERE DNIdocente = ?");
                $stmt->execute([$hash, $_SESSION['docente_id']]);
                $mensaje = "Contraseña actualizada correctamente";
            } else {
                $mensaje = "Las contraseñas nuevas no coinciden";
            }
        } else {
            $mensaje = "La contraseña actual es incorrecta";
        }
    } else {
        $mensaje = "Información actualizada correctamente";
    }
    
    // Actualizar datos en sesión
    $_SESSION['docente_nombres'] = $nombres;
    $_SESSION['docente_apellidos'] = $apellidos;
    
    // Recargar datos del docente
    $stmt = $pdo->prepare("SELECT d.*, GROUP_CONCAT(td.telefono SEPARATOR ', ') as telefonos 
                          FROM docente d 
                          LEFT JOIN telefono_docente td ON d.DNIdocente = td.DNIdocente 
                          WHERE d.DNIdocente = ? 
                          GROUP BY d.DNIdocente");
    $stmt->execute([$_SESSION['docente_id']]);
    $docente = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - IE Virgen de Fátima</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Configuración</h1>
                <p>Gestiona tu información personal y contraseña</p>
            </div>
            
            <?php if($mensaje): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <div class="settings-container">
                <form method="POST" class="settings-form">
                    <div class="form-section">
                        <h3>Información Personal</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dni">DNI</label>
                                <input type="text" id="dni" value="<?php echo $docente['DNIdocente']; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="nombres">Nombres</label>
                                <input type="text" id="nombres" name="nombres" value="<?php echo $docente['nombres']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="apellidos">Apellidos</label>
                                <input type="text" id="apellidos" name="apellidos" value="<?php echo $docente['apellidos']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="correo">Correo Electrónico</label>
                                <input type="email" id="correo" name="correo" value="<?php echo $docente['correo']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" value="<?php echo $docente['telefonos']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Cambiar Contraseña</h3>
                        
                        <div class="form-group">
                            <label for="contrasena_actual">Contraseña Actual</label>
                            <input type="password" id="contrasena_actual" name="contrasena_actual">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nueva_contrasena">Nueva Contraseña</label>
                                <input type="password" id="nueva_contrasena" name="nueva_contrasena">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmar_contrasena">Confirmar Contraseña</label>
                                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Guardar Cambios</button>
                        <button type="reset" class="btn-secondary">Cancelar</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>