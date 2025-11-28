<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

// Obtener estudiantes
$stmt = $pdo->prepare("SELECT e.*, gs.grado, gs.seccion 
                      FROM estudiante e 
                      JOIN gradoseccion gs ON e.id_grado_seccion = gs.id_grado_seccion");
$stmt->execute();
$estudiantes = $stmt->fetchAll();

// Filtrar estudiantes si se proporciona un filtro
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
if($filtro) {
    $estudiantes = array_filter($estudiantes, function($estudiante) use ($filtro) {
        return stripos($estudiante['nombres'], $filtro) !== false || 
               stripos($estudiante['apellidos'], $filtro) !== false ||
               stripos($estudiante['DNIestudiante'], $filtro) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes - Institución Nacional</title>
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
                <h1>Gestión de Estudiantes</h1>
                <p>Administra la información de los estudiantes</p>
            </div>
            
            <div class="filters-section">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <input type="text" name="filtro" placeholder="Buscar por nombre, apellido o DNI..." 
                               value="<?php echo htmlspecialchars($filtro); ?>">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Grado y Sección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($estudiantes as $estudiante): ?>
                        <tr>
                            <td><?php echo $estudiante['DNIestudiante']; ?></td>
                            <td><?php echo $estudiante['nombres']; ?></td>
                            <td><?php echo $estudiante['apellidos']; ?></td>
                            <td><?php echo $estudiante['grado'] . ' ' . $estudiante['seccion']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon" title="Ver perfil">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon" title="Registrar asistencia">
                                        <i class="fas fa-clipboard-check"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>