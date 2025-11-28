<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

$curso_seleccionado = isset($_GET['curso']) ? $_GET['curso'] : null;
$examen_seleccionado = isset($_GET['examen']) ? $_GET['examen'] : null;

// Obtener cursos del docente
$stmt = $pdo->prepare("SELECT * FROM curso WHERE DNIdocente = ?");
$stmt->execute([$_SESSION['docente_id']]);
$cursos = $stmt->fetchAll();

// Obtener exámenes si hay un curso seleccionado
$examenes = [];
if($curso_seleccionado) {
    $stmt = $pdo->prepare("SELECT * FROM examen WHERE codigo_curso = ? ORDER BY fecha DESC");
    $stmt->execute([$curso_seleccionado]);
    $examenes = $stmt->fetchAll();
}

// Obtener estudiantes y notas si hay un examen seleccionado
$estudiantes = [];
$notas_existentes = [];
if($examen_seleccionado) {
    // Obtener estudiantes
    $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE id_grado_seccion = 1 ORDER BY apellidos, nombres");
    $stmt->execute();
    $estudiantes = $stmt->fetchAll();
    
    // Obtener notas existentes
    $stmt = $pdo->prepare("SELECT * FROM notas_examen WHERE codigo_examen = ?");
    $stmt->execute([$examen_seleccionado]);
    $notas_data = $stmt->fetchAll();
    
    foreach($notas_data as $nota) {
        $notas_existentes[$nota['DNIestudiante']] = $nota;
    }
}

// Procesar guardado de notas de examen
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_notas_examen'])) {
    $examen_id = $_POST['examen_id'];
    $notas = $_POST['notas'];
    $observaciones = $_POST['observaciones'];
    
    try {
        foreach($notas as $dni => $nota_final) {
            $obs = isset($observaciones[$dni]) ? $observaciones[$dni] : '';
            
            // Verificar si ya existe una nota
            $stmt = $pdo->prepare("SELECT * FROM notas_examen WHERE DNIestudiante = ? AND codigo_examen = ?");
            $stmt->execute([$dni, $examen_id]);
            $nota_existente = $stmt->fetch();
            
            if($nota_existente) {
                // Actualizar nota existente
                $stmt = $pdo->prepare("UPDATE notas_examen SET nota_final = ?, observaciones = ? WHERE DNIestudiante = ? AND codigo_examen = ?");
                $stmt->execute([$nota_final, $obs, $dni, $examen_id]);
            } else {
                // Insertar nueva nota
                $stmt = $pdo->prepare("INSERT INTO notas_examen (DNIestudiante, codigo_examen, observaciones, nota_final) VALUES (?, ?, ?, ?)");
                $stmt->execute([$dni, $examen_id, $obs, $nota_final]);
            }
        }
        
        $_SESSION['success'] = "Notas de examen guardadas correctamente";
        header("Location: registrar_examen.php?examen=" . $examen_id);
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error al guardar notas: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Notas de Examen - Institución Nacional</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Registrar Notas de Examen</h1>
                <p>Gestiona las calificaciones de los estudiantes por examen</p>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="filters-section">
                <form method="GET" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="curso">Seleccionar Curso:</label>
                            <select id="curso" name="curso" onchange="this.form.submit()">
                                <option value="">-- Seleccione un curso --</option>
                                <?php foreach($cursos as $curso): ?>
                                <option value="<?php echo $curso['codigo_curso']; ?>" 
                                    <?php echo ($curso_seleccionado == $curso['codigo_curso']) ? 'selected' : ''; ?>>
                                    <?php echo $curso['nombre']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if($curso_seleccionado): ?>
                        <div class="form-group">
                            <label for="examen">Seleccionar Examen:</label>
                            <select id="examen" name="examen" onchange="this.form.submit()">
                                <option value="">-- Seleccione un examen --</option>
                                <?php foreach($examenes as $examen): ?>
                                <option value="<?php echo $examen['codigo_examen']; ?>" 
                                    <?php echo ($examen_seleccionado == $examen['codigo_examen']) ? 'selected' : ''; ?>>
                                    <?php echo $examen['tipo_examen']; ?> - <?php echo date('d/m/Y', strtotime($examen['fecha'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php if($examen_seleccionado && count($estudiantes) > 0): 
                // Obtener información del examen
                $stmt = $pdo->prepare("SELECT e.*, c.nombre as curso_nombre 
                                      FROM examen e 
                                      JOIN curso c ON e.codigo_curso = c.codigo_curso 
                                      WHERE e.codigo_examen = ?");
                $stmt->execute([$examen_seleccionado]);
                $examen_info = $stmt->fetch();
            ?>
            <div class="notes-container">
                <div class="exam-info-card">
                    <h3>Información del Examen</h3>
                    <div class="exam-details">
                        <p><strong>Curso:</strong> <?php echo $examen_info['curso_nombre']; ?></p>
                        <p><strong>Tipo:</strong> <?php echo $examen_info['tipo_examen']; ?></p>
                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($examen_info['fecha'])); ?></p>
                        <p><strong>Descripción:</strong> <?php echo $examen_info['descripcion']; ?></p>
                        <p><strong>Peso en promedio:</strong> <?php echo number_format($examen_info['peso'], 2); ?>%</p>
                    </div>
                </div>
                
                <form method="POST" class="notes-form">
                    <input type="hidden" name="examen_id" value="<?php echo $examen_seleccionado; ?>">
                    <input type="hidden" name="guardar_notas_examen" value="1">
                    
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Registro de Notas de Examen</h3>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Guardar Todas las Notas
                            </button>
                        </div>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>DNI</th>
                                    <th>Estudiante</th>
                                    <th>Nota Final</th>
                                    <th>Observaciones</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($estudiantes as $estudiante): 
                                    $nota_existente = isset($notas_existentes[$estudiante['DNIestudiante']]) ? $notas_existentes[$estudiante['DNIestudiante']] : null;
                                ?>
                                <tr>
                                    <td><?php echo $estudiante['DNIestudiante']; ?></td>
                                    <td><?php echo $estudiante['nombres'] . ' ' . $estudiante['apellidos']; ?></td>
                                    <td>
                                        <select name="notas[<?php echo $estudiante['DNIestudiante']; ?>]" class="note-select">
                                            <option value="">-- Seleccionar --</option>
                                            <option value="AD" <?php echo ($nota_existente && $nota_existente['nota_final'] == 'AD') ? 'selected' : ''; ?>>AD - Logro destacado</option>
                                            <option value="A" <?php echo ($nota_existente && $nota_existente['nota_final'] == 'A') ? 'selected' : ''; ?>>A - Logro esperado</option>
                                            <option value="B" <?php echo ($nota_existente && $nota_existente['nota_final'] == 'B') ? 'selected' : ''; ?>>B - En proceso</option>
                                            <option value="C" <?php echo ($nota_existente && $nota_existente['nota_final'] == 'C') ? 'selected' : ''; ?>>C - En inicio</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="observaciones[<?php echo $estudiante['DNIestudiante']; ?>]" 
                                               value="<?php echo $nota_existente ? htmlspecialchars($nota_existente['observaciones']) : ''; ?>" 
                                               placeholder="Observaciones..." class="obs-input">
                                    </td>
                                    <td>
                                        <?php if($nota_existente): ?>
                                            <span class="status-badge status-completed">
                                                <i class="fas fa-check-circle"></i> Calificado
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock"></i> Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <?php elseif($examen_seleccionado): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No hay estudiantes</h3>
                <p>No se encontraron estudiantes para esta sección.</p>
            </div>
            <?php elseif($curso_seleccionado): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>Selecciona un examen</h3>
                <p>Por favor, selecciona un examen para registrar las notas.</p>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-filter"></i>
                <h3>Selecciona un curso</h3>
                <p>Por favor, selecciona un curso para comenzar a registrar notas de examen.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>