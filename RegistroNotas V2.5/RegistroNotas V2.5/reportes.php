<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

$curso_seleccionado = isset($_GET['curso']) ? $_GET['curso'] : null;

// Obtener cursos del docente
$stmt = $pdo->prepare("SELECT * FROM curso WHERE DNIdocente = ?");
$stmt->execute([$_SESSION['docente_id']]);
$cursos = $stmt->fetchAll();

// Obtener notas si hay un curso seleccionado
$notas = [];
$curso_info = null;
$examenes = [];
if($curso_seleccionado) {
    // Obtener información del curso
    $stmt = $pdo->prepare("SELECT * FROM curso WHERE codigo_curso = ?");
    $stmt->execute([$curso_seleccionado]);
    $curso_info = $stmt->fetch();
    
    // Obtener todas las sesiones del curso
    $stmt = $pdo->prepare("SELECT * FROM sesion_de_aprendizaje WHERE codigo_curso = ? ORDER BY fecha");
    $stmt->execute([$curso_seleccionado]);
    $sesiones = $stmt->fetchAll();
    
    // Obtener exámenes del curso
    $stmt = $pdo->prepare("SELECT * FROM examen WHERE codigo_curso = ? ORDER BY fecha");
    $stmt->execute([$curso_seleccionado]);
    $examenes = $stmt->fetchAll();
    
    // Obtener estudiantes
    $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE id_grado_seccion = 1 ORDER BY apellidos, nombres");
    $stmt->execute();
    $estudiantes = $stmt->fetchAll();
    
    // Obtener notas de todas las sesiones y exámenes
    $notas_completas = [];
    foreach($estudiantes as $estudiante) {
        $notas_estudiante = [
            'estudiante' => $estudiante,
            'notas' => [],
            'notas_examen' => [],
            'promedio_sesiones' => 0,
            'promedio_examenes' => 0,
            'promedio_final' => 0
        ];
        
        // Calcular promedio de sesiones
        $total_puntos_sesiones = 0;
        $sesiones_calificadas = 0;
        
        foreach($sesiones as $sesion) {
            $stmt = $pdo->prepare("SELECT * FROM notas WHERE DNIestudiante = ? AND codigo_sesion = ?");
            $stmt->execute([$estudiante['DNIestudiante'], $sesion['codigo_sesion']]);
            $nota = $stmt->fetch();
            
            $valor_nota = notaToValor($nota['nota_final'] ?? '');
            $notas_estudiante['notas'][$sesion['codigo_sesion']] = [
                'nota' => $nota,
                'valor' => $valor_nota,
                'sesion' => $sesion
            ];
            
            if ($valor_nota > 0) {
                $total_puntos_sesiones += $valor_nota;
                $sesiones_calificadas++;
            }
        }
        
        // Calcular promedio de exámenes
        $total_puntos_examenes = 0;
        $examenes_calificados = 0;
        $peso_total_examenes = 0;
        
        foreach($examenes as $examen) {
            $stmt = $pdo->prepare("SELECT * FROM notas_examen WHERE DNIestudiante = ? AND codigo_examen = ?");
            $stmt->execute([$estudiante['DNIestudiante'], $examen['codigo_examen']]);
            $nota_examen = $stmt->fetch();
            
            $valor_nota_examen = notaToValor($nota_examen['nota_final'] ?? '');
            $notas_estudiante['notas_examen'][$examen['codigo_examen']] = [
                'nota' => $nota_examen,
                'valor' => $valor_nota_examen,
                'examen' => $examen
            ];
            
            if ($valor_nota_examen > 0) {
                $total_puntos_examenes += $valor_nota_examen * $examen['peso'];
                $peso_total_examenes += $examen['peso'];
                $examenes_calificados++;
            }
        }
        
        // Calcular promedios
        $notas_estudiante['promedio_sesiones'] = $sesiones_calificadas > 0 ? $total_puntos_sesiones / $sesiones_calificadas : 0;
        $notas_estudiante['promedio_examenes'] = $peso_total_examenes > 0 ? $total_puntos_examenes / $peso_total_examenes : 0;
        
        // Calcular promedio final (70% sesiones + 30% exámenes)
        $peso_sesiones = 0.7;
        $peso_examenes = 0.3;
        
        if ($sesiones_calificadas > 0 && $examenes_calificados > 0) {
            $notas_estudiante['promedio_final'] = 
                ($notas_estudiante['promedio_sesiones'] * $peso_sesiones) + 
                ($notas_estudiante['promedio_examenes'] * $peso_examenes);
        } elseif ($sesiones_calificadas > 0) {
            $notas_estudiante['promedio_final'] = $notas_estudiante['promedio_sesiones'];
        } elseif ($examenes_calificados > 0) {
            $notas_estudiante['promedio_final'] = $notas_estudiante['promedio_examenes'];
        } else {
            $notas_estudiante['promedio_final'] = 0;
        }
        
        $notas_completas[] = $notas_estudiante;
    }
    
    $notas = $notas_completas;
}

// Función para convertir nota a valor numérico
function notaToValor($nota) {
    switch($nota) {
        case 'AD': return 4;
        case 'A': return 3;
        case 'B': return 2;
        case 'C': return 1;
        default: return 0;
    }
}

function valorToNota($valor) {
    if ($valor >= 3.5) return 'AD';
    if ($valor >= 2.5) return 'A';
    if ($valor >= 1.5) return 'B';
    return 'C';
}

// Exportar a Excel - REPORTE NORMAL (solo sesiones)
if(isset($_GET['export']) && $_GET['export'] == 'excel' && $curso_seleccionado) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="notas_sesiones_' . $curso_info['nombre'] . '_' . date('Y-m-d') . '.xls"');
    
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
    echo "<tr><td colspan='" . (count($sesiones) + 4) . "' class='excel-header'>";
    echo "<div class='excel-title'>Institución Nacional'</div>";
    echo "<div class='excel-subtitle'>REPORTE DE NOTAS - SESIONES - " . strtoupper($curso_info['nombre']) . "</div>";
    echo "<div class='excel-subtitle'>Fecha de emisión: " . date('d/m/Y') . "</div>";
    echo "</td></tr>";
    
    // Encabezado de la tabla
    echo "<tr>";
    echo "<th>N°</th>";
    echo "<th>DNI</th>";
    echo "<th>ESTUDIANTE</th>";
    
    foreach($sesiones as $sesion) {
        echo "<th>" . substr($sesion['titulo'], 0, 15) . "<br>" . date('d/m', strtotime($sesion['fecha'])) . "</th>";
    }
    
    echo "<th>PROMEDIO SESIONES</th>";
    echo "<th>OBSERVACIONES</th>";
    echo "</tr>";
    
    // Datos de los estudiantes
    $numero = 1;
    foreach($notas as $nota_estudiante) {
        $estudiante = $nota_estudiante['estudiante'];
        
        echo "<tr>";
        echo "<td>" . $numero++ . "</td>";
        echo "<td>" . $estudiante['DNIestudiante'] . "</td>";
        echo "<td>" . $estudiante['apellidos'] . ", " . $estudiante['nombres'] . "</td>";
        
        $observaciones = [];
        foreach($sesiones as $sesion) {
            $nota_sesion = $nota_estudiante['notas'][$sesion['codigo_sesion']];
            $nota_valor = $nota_sesion['nota']['nota_final'] ?? '';
            $clase_nota = getNotaClass($nota_valor);
            
            echo "<td class='" . $clase_nota . "'>" . $nota_valor . "</td>";
            
            if (!empty($nota_sesion['nota']['observaciones'])) {
                $observaciones[] = "Sesión " . date('d/m', strtotime($sesion['fecha'])) . ": " . $nota_sesion['nota']['observaciones'];
            }
        }
        
        // Promedio sesiones
        $promedio_nota = valorToNota($nota_estudiante['promedio_sesiones']);
        echo "<td class='" . getNotaClass($promedio_nota) . "'>" . number_format($nota_estudiante['promedio_sesiones'], 2) . " (" . $promedio_nota . ")</td>";
        
        // Observaciones
        echo "<td>" . implode("; ", $observaciones) . "</td>";
        echo "</tr>";
    }
    
    // Resumen estadístico
    echo "<tr><td colspan='" . (count($sesiones) + 4) . "' style='background-color: #f8f9fa; font-weight: bold;'>RESUMEN ESTADÍSTICO</td></tr>";
    
    // Distribución de promedios
    $distribucion = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0];
    foreach($notas as $nota_estudiante) {
        $promedio_nota = valorToNota($nota_estudiante['promedio_sesiones']);
        $distribucion[$promedio_nota]++;
    }
    
    echo "<tr>";
    echo "<td colspan='3'><strong>Distribución de Promedios:</strong></td>";
    echo "<td colspan='" . (count($sesiones) + 1) . "'>";
    echo "AD: " . $distribucion['AD'] . " | A: " . $distribucion['A'] . " | B: " . $distribucion['B'] . " | C: " . $distribucion['C'];
    echo " | Total: " . count($notas);
    echo "</td>";
    echo "</tr>";
    
    echo "</table>";
    echo "</body>";
    echo "</html>";
    exit();
}

// Exportar a Excel - REPORTE COMPLETO (sesiones + exámenes)
if(isset($_GET['export']) && $_GET['export'] == 'excel_completo' && $curso_seleccionado) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="notas_completas_' . $curso_info['nombre'] . '_' . date('Y-m-d') . '.xls"');
    
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
    echo ".section-header { background-color: #e9ecef; font-weight: bold; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    
    // Encabezado del Excel
    echo "<table>";
    echo "<tr><td colspan='" . (count($sesiones) + count($examenes) + 5) . "' class='excel-header'>";
    echo "<div class='excel-title'>INSTITUCIÓN NACIONAL</div>";
    echo "<div class='excel-subtitle'>REPORTE COMPLETO DE NOTAS - " . strtoupper($curso_info['nombre']) . "</div>";
    echo "<div class='excel-subtitle'>Fecha de emisión: " . date('d/m/Y') . "</div>";
    echo "</td></tr>";
    
    // Encabezado de la tabla
    echo "<tr>";
    echo "<th>N°</th>";
    echo "<th>DNI</th>";
    echo "<th>ESTUDIANTE</th>";
    
    // Sesiones
    if(count($sesiones) > 0) {
        echo "<th colspan='" . count($sesiones) . "' class='section-header'>SESIONES DE APRENDIZAJE</th>";
    }
    
    // Exámenes
    if(count($examenes) > 0) {
        echo "<th colspan='" . count($examenes) . "' class='section-header'>EXÁMENES</th>";
    }
    
    echo "<th>PROM. SESIONES</th>";
    echo "<th>PROM. EXÁMENES</th>";
    echo "<th>PROMEDIO FINAL</th>";
    echo "<th>OBSERVACIONES</th>";
    echo "</tr>";
    
    // Segunda fila de encabezados
    echo "<tr>";
    echo "<th></th><th></th><th></th>";
    
    // Sesiones
    foreach($sesiones as $sesion) {
        echo "<th>" . substr($sesion['titulo'], 0, 12) . "<br>" . date('d/m', strtotime($sesion['fecha'])) . "</th>";
    }
    
    // Exámenes
    foreach($examenes as $examen) {
        echo "<th>" . substr($examen['tipo_examen'], 0, 8) . "<br>" . date('d/m', strtotime($examen['fecha'])) . "</th>";
    }
    
    echo "<th></th><th></th><th></th><th></th>";
    echo "</tr>";
    
    // Datos de los estudiantes
    $numero = 1;
    foreach($notas as $nota_estudiante) {
        $estudiante = $nota_estudiante['estudiante'];
        
        echo "<tr>";
        echo "<td>" . $numero++ . "</td>";
        echo "<td>" . $estudiante['DNIestudiante'] . "</td>";
        echo "<td>" . $estudiante['apellidos'] . ", " . $estudiante['nombres'] . "</td>";
        
        $observaciones = [];
        
        // Notas de sesiones
        foreach($sesiones as $sesion) {
            $nota_sesion = $nota_estudiante['notas'][$sesion['codigo_sesion']];
            $nota_valor = $nota_sesion['nota']['nota_final'] ?? '';
            $clase_nota = getNotaClass($nota_valor);
            
            echo "<td class='" . $clase_nota . "'>" . $nota_valor . "</td>";
            
            if (!empty($nota_sesion['nota']['observaciones'])) {
                $observaciones[] = "Sesión " . date('d/m', strtotime($sesion['fecha'])) . ": " . $nota_sesion['nota']['observaciones'];
            }
        }
        
        // Notas de exámenes
        foreach($examenes as $examen) {
            $nota_examen = $nota_estudiante['notas_examen'][$examen['codigo_examen']];
            $nota_valor = $nota_examen['nota']['nota_final'] ?? '';
            $clase_nota = getNotaClass($nota_valor);
            
            echo "<td class='" . $clase_nota . "'>" . $nota_valor . "</td>";
            
            if (!empty($nota_examen['nota']['observaciones'])) {
                $observaciones[] = "Examen " . $examen['tipo_examen'] . ": " . $nota_examen['nota']['observaciones'];
            }
        }
        
        // Promedios
        $promedio_sesiones_nota = valorToNota($nota_estudiante['promedio_sesiones']);
        $promedio_examenes_nota = valorToNota($nota_estudiante['promedio_examenes']);
        $promedio_final_nota = valorToNota($nota_estudiante['promedio_final']);
        
        echo "<td class='" . getNotaClass($promedio_sesiones_nota) . "'>" . number_format($nota_estudiante['promedio_sesiones'], 2) . "</td>";
        echo "<td class='" . getNotaClass($promedio_examenes_nota) . "'>" . number_format($nota_estudiante['promedio_examenes'], 2) . "</td>";
        echo "<td class='" . getNotaClass($promedio_final_nota) . "'>" . number_format($nota_estudiante['promedio_final'], 2) . " (" . $promedio_final_nota . ")</td>";
        
        // Observaciones
        echo "<td>" . implode("; ", $observaciones) . "</td>";
        echo "</tr>";
    }
    
    // Resumen estadístico
    echo "<tr><td colspan='" . (count($sesiones) + count($examenes) + 5) . "' style='background-color: #f8f9fa; font-weight: bold;'>RESUMEN ESTADÍSTICO - PROMEDIO FINAL</td></tr>";
    
    // Distribución de promedios finales
    $distribucion = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0];
    foreach($notas as $nota_estudiante) {
        $promedio_nota = valorToNota($nota_estudiante['promedio_final']);
        $distribucion[$promedio_nota]++;
    }
    
    echo "<tr>";
    echo "<td colspan='3'><strong>Distribución de Promedios Finales:</strong></td>";
    echo "<td colspan='" . (count($sesiones) + count($examenes) + 2) . "'>";
    echo "AD: " . $distribucion['AD'] . " | A: " . $distribucion['A'] . " | B: " . $distribucion['B'] . " | C: " . $distribucion['C'];
    echo " | Total: " . count($notas);
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
    <title>Reportes - Institución Nacional</title>
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
                <h1>Reporte de Notas</h1>
                <p>Consulta y descarga las calificaciones por curso</p>
            </div>
            
            <div class="filters-section">
                <form method="GET" class="filter-form">
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
                </form>
            </div>
            
            <?php if($curso_seleccionado && count($notas) > 0): ?>
            <div class="report-actions">
                <div class="export-buttons">
                    <a href="?curso=<?php echo $curso_seleccionado; ?>&export=excel" class="btn-export">
                        <i class="fas fa-file-excel"></i> Exportar Sesiones
                    </a>
                    <a href="?curso=<?php echo $curso_seleccionado; ?>&export=excel_completo" class="btn-export btn-export-completo">
                        <i class="fas fa-file-excel"></i> Exportar Completo
                    </a>
                </div>
                <div class="report-info">
                    <span class="report-stat">
                        <i class="fas fa-users"></i> <?php echo count($notas); ?> Estudiantes
                    </span>
                    <span class="report-stat">
                        <i class="fas fa-calendar-alt"></i> <?php echo count($sesiones); ?> Sesiones
                    </span>
                    <span class="report-stat">
                        <i class="fas fa-file-alt"></i> <?php echo count($examenes); ?> Exámenes
                    </span>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h3>Notas del Curso: <?php echo $curso_info['nombre']; ?></h3>
                    <div class="view-toggle">
                        <button class="btn-toggle active" data-view="sesiones">Solo Sesiones</button>
                        <button class="btn-toggle" data-view="completo">Vista Completa</button>
                    </div>
                </div>
                
                <!-- Vista Solo Sesiones -->
                <div class="table-view" id="view-sesiones">
                    <div class="table-wrapper">
                        <table class="data-table report-table">
                            <thead>
                                <tr>
                                    <th rowspan="2">N°</th>
                                    <th rowspan="2">DNI</th>
                                    <th rowspan="2">Estudiante</th>
                                    <th colspan="<?php echo count($sesiones); ?>">Sesiones de Aprendizaje</th>
                                    <th rowspan="2">Promedio Sesiones</th>
                                    <th rowspan="2">Estado</th>
                                </tr>
                                <tr>
                                    <?php foreach($sesiones as $sesion): ?>
                                    <th title="<?php echo $sesion['titulo']; ?>">
                                        <?php echo substr($sesion['titulo'], 0, 15) . '...'; ?><br>
                                        <small><?php echo date('d/m', strtotime($sesion['fecha'])); ?></small>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($notas as $index => $nota_estudiante): 
                                    $estudiante = $nota_estudiante['estudiante'];
                                    $promedio = $nota_estudiante['promedio_sesiones'];
                                    $promedio_nota = valorToNota($promedio);
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $estudiante['DNIestudiante']; ?></td>
                                    <td>
                                        <strong><?php echo $estudiante['apellidos']; ?></strong><br>
                                        <small><?php echo $estudiante['nombres']; ?></small>
                                    </td>
                                    
                                    <?php foreach($sesiones as $sesion): 
                                        $nota_sesion = $nota_estudiante['notas'][$sesion['codigo_sesion']];
                                        $nota_valor = $nota_sesion['nota']['nota_final'] ?? '';
                                    ?>
                                    <td class="text-center">
                                        <span class="nota-badge <?php echo getNotaClass($nota_valor); ?>">
                                            <?php echo $nota_valor ?: '--'; ?>
                                        </span>
                                        <?php if(!empty($nota_sesion['nota']['observaciones'])): ?>
                                        <br><small title="<?php echo htmlspecialchars($nota_sesion['nota']['observaciones']); ?>">
                                            <i class="fas fa-comment"></i>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                    
                                    <td class="text-center">
                                        <strong class="nota-badge <?php echo getNotaClass($promedio_nota); ?>">
                                            <?php echo number_format($promedio, 2); ?><br>
                                            <small>(<?php echo $promedio_nota; ?>)</small>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if($promedio >= 3.5): ?>
                                            <span class="status-badge status-completed">Excelente</span>
                                        <?php elseif($promedio >= 2.5): ?>
                                            <span class="status-badge status-good">Bueno</span>
                                        <?php elseif($promedio >= 1.5): ?>
                                            <span class="status-badge status-warning">Regular</span>
                                        <?php else: ?>
                                            <span class="status-badge status-danger">Necesita mejorar</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Vista Completa -->
                <div class="table-view" id="view-completo" style="display: none;">
                    <div class="table-wrapper">
                        <table class="data-table report-table">
                            <thead>
                                <tr>
                                    <th rowspan="2">N°</th>
                                    <th rowspan="2">DNI</th>
                                    <th rowspan="2">Estudiante</th>
                                    
                                    <?php if(count($sesiones) > 0): ?>
                                    <th colspan="<?php echo count($sesiones); ?>" class="section-header">Sesiones de Aprendizaje</th>
                                    <?php endif; ?>
                                    
                                    <?php if(count($examenes) > 0): ?>
                                    <th colspan="<?php echo count($examenes); ?>" class="section-header">Exámenes</th>
                                    <?php endif; ?>
                                    
                                    <th rowspan="2">Prom. Sesiones</th>
                                    <th rowspan="2">Prom. Exámenes</th>
                                    <th rowspan="2">Promedio Final</th>
                                    <th rowspan="2">Estado Final</th>
                                </tr>
                                <tr>
                                    <!-- Sesiones -->
                                    <?php foreach($sesiones as $sesion): ?>
                                    <th title="<?php echo $sesion['titulo']; ?>">
                                        <?php echo substr($sesion['titulo'], 0, 12) . '...'; ?><br>
                                        <small><?php echo date('d/m', strtotime($sesion['fecha'])); ?></small>
                                    </th>
                                    <?php endforeach; ?>
                                    
                                    <!-- Exámenes -->
                                    <?php foreach($examenes as $examen): ?>
                                    <th title="<?php echo $examen['tipo_examen'] . ' - ' . $examen['descripcion']; ?>">
                                        <?php echo substr($examen['tipo_examen'], 0, 10); ?><br>
                                        <small><?php echo date('d/m', strtotime($examen['fecha'])); ?></small>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($notas as $index => $nota_estudiante): 
                                    $estudiante = $nota_estudiante['estudiante'];
                                    $promedio_final = $nota_estudiante['promedio_final'];
                                    $promedio_final_nota = valorToNota($promedio_final);
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $estudiante['DNIestudiante']; ?></td>
                                    <td>
                                        <strong><?php echo $estudiante['apellidos']; ?></strong><br>
                                        <small><?php echo $estudiante['nombres']; ?></small>
                                    </td>
                                    
                                    <!-- Notas de sesiones -->
                                    <?php foreach($sesiones as $sesion): 
                                        $nota_sesion = $nota_estudiante['notas'][$sesion['codigo_sesion']];
                                        $nota_valor = $nota_sesion['nota']['nota_final'] ?? '';
                                    ?>
                                    <td class="text-center">
                                        <span class="nota-badge <?php echo getNotaClass($nota_valor); ?>">
                                            <?php echo $nota_valor ?: '--'; ?>
                                        </span>
                                    </td>
                                    <?php endforeach; ?>
                                    
                                    <!-- Notas de exámenes -->
                                    <?php foreach($examenes as $examen): 
                                        $nota_examen = $nota_estudiante['notas_examen'][$examen['codigo_examen']];
                                        $nota_valor = $nota_examen['nota']['nota_final'] ?? '';
                                    ?>
                                    <td class="text-center">
                                        <span class="nota-badge <?php echo getNotaClass($nota_valor); ?>">
                                            <?php echo $nota_valor ?: '--'; ?>
                                        </span>
                                    </td>
                                    <?php endforeach; ?>
                                    
                                    <!-- Promedios -->
                                    <td class="text-center">
                                        <strong><?php echo number_format($nota_estudiante['promedio_sesiones'], 2); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo number_format($nota_estudiante['promedio_examenes'], 2); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <strong class="nota-badge <?php echo getNotaClass($promedio_final_nota); ?>">
                                            <?php echo number_format($promedio_final, 2); ?><br>
                                            <small>(<?php echo $promedio_final_nota; ?>)</small>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if($promedio_final >= 3.5): ?>
                                            <span class="status-badge status-completed">Excelente</span>
                                        <?php elseif($promedio_final >= 2.5): ?>
                                            <span class="status-badge status-good">Bueno</span>
                                        <?php elseif($promedio_final >= 1.5): ?>
                                            <span class="status-badge status-warning">Regular</span>
                                        <?php else: ?>
                                            <span class="status-badge status-danger">Necesita mejorar</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Resumen estadístico -->
                <div class="stats-summary">
                    <h4>Resumen Estadístico - Promedio Final</h4>
                    <div class="stats-grid-small">
                        <?php
                        $distribucion = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0];
                        $total_notas = 0;
                        $suma_promedios = 0;
                        
                        foreach($notas as $nota_estudiante) {
                            $promedio_nota = valorToNota($nota_estudiante['promedio_final']);
                            $distribucion[$promedio_nota]++;
                            $suma_promedios += $nota_estudiante['promedio_final'];
                            $total_notas++;
                        }
                        
                        $promedio_general = $total_notas > 0 ? $suma_promedios / $total_notas : 0;
                        ?>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($promedio_general, 2); ?></div>
                            <div class="stat-label">Promedio General</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $distribucion['AD']; ?></div>
                            <div class="stat-label">Logro Destacado (AD)</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $distribucion['A']; ?></div>
                            <div class="stat-label">Logro Esperado (A)</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $distribucion['B']; ?></div>
                            <div class="stat-label">En Proceso (B)</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $distribucion['C']; ?></div>
                            <div class="stat-label">En Inicio (C)</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif($curso_seleccionado): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No hay estudiantes</h3>
                <p>No se encontraron estudiantes para este curso.</p>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-filter"></i>
                <h3>Selecciona un curso</h3>
                <p>Por favor, selecciona un curso para ver el reporte de notas.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="js/script.js"></script>
    <script>
    // Toggle entre vistas
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.btn-toggle');
        const tableViews = document.querySelectorAll('.table-view');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const view = this.getAttribute('data-view');
                
                // Actualizar botones activos
                toggleButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Mostrar vista correspondiente
                tableViews.forEach(viewElement => {
                    viewElement.style.display = 'none';
                });
                
                document.getElementById('view-' + view).style.display = 'block';
            });
        });
    });
    </script>
</body>
</html>

<?php
function getNotaClass($nota) {
    if($nota == 'AD') return 'nota-excelente';
    if($nota == 'A') return 'nota-buena';
    if($nota == 'B') return 'nota-regular';
    if($nota == 'C') return 'nota-baja';
    return 'nota-sin-calificar';
}
?>