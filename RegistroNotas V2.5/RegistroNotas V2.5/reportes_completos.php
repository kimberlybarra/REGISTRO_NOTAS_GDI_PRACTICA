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

// Exportar a Excel - REPORTE COMPLETO DE EXAMENES
if(isset($_GET['export']) && $_GET['export'] == 'excel' && $examen_seleccionado) {
    // Obtener información del examen
    $stmt = $pdo->prepare("SELECT e.*, c.nombre as curso_nombre 
                          FROM examen e 
                          JOIN curso c ON e.codigo_curso = c.codigo_curso 
                          WHERE e.codigo_examen = ?");
    $stmt->execute([$examen_seleccionado]);
    $examen_info = $stmt->fetch();
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="notas_examen_' . $examen_info['tipo_examen'] . '_' . date('Y-m-d') . '.xls"');
    
    echo "<html>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th, td { border: 1px solid #000; padding: 8px; text-align: left; }";
    echo "th { background-color: #3578e5; color: white; }";
    echo ".excel-header { background-color: #2c3e50; color: white; text-align: center; padding: 10px; }";
    echo ".excel-title { font-size: 18px; font-weight: bold; }";
    echo ".excel-subtitle { font-size: 14px; }";
    echo ".nota-ad { background-color: #d4edda; }";
    echo ".nota-a { background-color: #d1ecf1; }";
    echo ".nota-b { background-color: #fff3cd; }";
    echo ".nota-c { background-color: #f8d7da; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    
    // Encabezado del Excel
    echo "<table>";
    echo "<tr><td colspan='5' class='excel-header'>";
    echo "<div class='excel-title'>INSTITUCIÓN NACIONAL</div>";
    echo "<div class='excel-subtitle'>REPORTE DE NOTAS - " . strtoupper($examen_info['tipo_examen']) . " - " . strtoupper($examen_info['curso_nombre']) . "</div>";
    echo "<div class='excel-subtitle'>Fecha del examen: " . date('d/m/Y', strtotime($examen_info['fecha'])) . " | Emisión: " . date('d/m/Y') . "</div>";
    echo "</td></tr>";
    
    // Encabezado de la tabla
    echo "<tr>";
    echo "<th>N°</th>";
    echo "<th>DNI</th>";
    echo "<th>ESTUDIANTE</th>";
    echo "<th>NOTA FINAL</th>";
    echo "<th>OBSERVACIONES</th>";
    echo "</tr>";
    
    // Datos de los estudiantes
    $numero = 1;
    foreach($estudiantes as $estudiante) {
        $nota_existente = isset($notas_existentes[$estudiante['DNIestudiante']]) ? $notas_existentes[$estudiante['DNIestudiante']] : null;
        $nota_valor = $nota_existente ? $nota_existente['nota_final'] : '';
        $observaciones = $nota_existente ? $nota_existente['observaciones'] : '';
        
        $clase_nota = getNotaClass($nota_valor);
        
        echo "<tr>";
        echo "<td>" . $numero++ . "</td>";
        echo "<td>" . $estudiante['DNIestudiante'] . "</td>";
        echo "<td>" . $estudiante['apellidos'] . ", " . $estudiante['nombres'] . "</td>";
        echo "<td class='" . $clase_nota . "'>" . $nota_valor . "</td>";
        echo "<td>" . $observaciones . "</td>";
        echo "</tr>";
    }
    
    // Resumen estadístico
    echo "<tr><td colspan='5' style='background-color: #f8f9fa; font-weight: bold;'>RESUMEN ESTADÍSTICO</td></tr>";
    
    // Distribución de notas
    $distribucion = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'Sin calificar' => 0];
    foreach($estudiantes as $estudiante) {
        $nota_existente = isset($notas_existentes[$estudiante['DNIestudiante']]) ? $notas_existentes[$estudiante['DNIestudiante']] : null;
        $nota_valor = $nota_existente ? $nota_existente['nota_final'] : 'Sin calificar';
        
        if(isset($distribucion[$nota_valor])) {
            $distribucion[$nota_valor]++;
        } else {
            $distribucion['Sin calificar']++;
        }
    }
    
    echo "<tr>";
    echo "<td colspan='3'><strong>Distribución de Notas:</strong></td>";
    echo "<td colspan='2'>";
    echo "AD: " . $distribucion['AD'] . " | A: " . $distribucion['A'] . " | B: " . $distribucion['B'] . " | C: " . $distribucion['C'] . " | Sin calificar: " . $distribucion['Sin calificar'];
    echo " | Total: " . count($estudiantes);
    echo "</td>";
    echo "</tr>";
    
    echo "</table>";
    echo "</body>";
    echo "</html>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Notas de Examen - IE Virgen de Fátima</title>
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
                    <div class="exam-actions">
                        <a href="?curso=<?php echo $curso_seleccionado; ?>&examen=<?php echo $examen_seleccionado; ?>&export=excel" class="btn-export">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </a>
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

<?php
function getNotaClass($nota) {
    if($nota == 'AD') return 'nota-ad';
    if($nota == 'A') return 'nota-a';
    if($nota == 'B') return 'nota-b';
    if($nota == 'C') return 'nota-c';
    return '';
}
?>