<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

$sesion_seleccionada = isset($_GET['sesion']) ? $_GET['sesion'] : null;

if(!$sesion_seleccionada) {
    header("Location: cursos.php");
    exit();
}

// Obtener información de la sesión
$stmt = $pdo->prepare("SELECT sa.*, c.nombre as curso_nombre, lc.codigo_listacot 
                      FROM sesion_de_aprendizaje sa 
                      JOIN curso c ON sa.codigo_curso = c.codigo_curso 
                      LEFT JOIN lista_de_cotejo lc ON sa.codigo_sesion = lc.codigo_sesion 
                      WHERE sa.codigo_sesion = ?");
$stmt->execute([$sesion_seleccionada]);
$sesion_info = $stmt->fetch();

// Obtener criterios de la lista de cotejo
$criterios = [];
if($sesion_info['codigo_listacot']) {
    $stmt = $pdo->prepare("SELECT * FROM criterio WHERE codigo_listacot = ? ORDER BY id_criterio");
    $stmt->execute([$sesion_info['codigo_listacot']]);
    $criterios = $stmt->fetchAll();
}

// Obtener estudiantes
$stmt = $pdo->prepare("SELECT * FROM estudiante WHERE id_grado_seccion = 1 ORDER BY apellidos, nombres");
$stmt->execute();
$estudiantes = $stmt->fetchAll();

// Obtener evaluaciones existentes por criterio
$evaluaciones_detalle = [];
if($sesion_info['codigo_listacot'] && count($criterios) > 0) {
    $stmt = $pdo->prepare("SELECT * FROM evaluacion_detalle WHERE codigo_listacot = ?");
    $stmt->execute([$sesion_info['codigo_listacot']]);
    $eval_data = $stmt->fetchAll();
    
    foreach($eval_data as $eval) {
        $evaluaciones_detalle[$eval['DNIestudiante']][$eval['id_criterio']] = $eval;
    }
}

// Procesar guardado de evaluaciones
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_evaluaciones'])) {
    $codigo_listacot = $sesion_info['codigo_listacot'];
    $evaluaciones = $_POST['evaluaciones'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        foreach($evaluaciones as $dni_estudiante => $criterios_eval) {
            foreach($criterios_eval as $id_criterio => $cumplido) {
                $cumplido_bool = ($cumplido === 'on') ? 1 : 0;
                
                // Verificar si ya existe una evaluación para este estudiante y criterio
                $stmt = $pdo->prepare("SELECT * FROM evaluacion_detalle 
                                      WHERE DNIestudiante = ? AND codigo_listacot = ? AND id_criterio = ?");
                $stmt->execute([$dni_estudiante, $codigo_listacot, $id_criterio]);
                $evaluacion_existente = $stmt->fetch();
                
                if($evaluacion_existente) {
                    // Actualizar evaluación existente
                    $stmt = $pdo->prepare("UPDATE evaluacion_detalle SET cumplido = ? 
                                          WHERE DNIestudiante = ? AND codigo_listacot = ? AND id_criterio = ?");
                    $stmt->execute([$cumplido_bool, $dni_estudiante, $codigo_listacot, $id_criterio]);
                } else {
                    // Insertar nueva evaluación
                    $stmt = $pdo->prepare("INSERT INTO evaluacion_detalle (DNIestudiante, codigo_listacot, id_criterio, cumplido) 
                                          VALUES (?, ?, ?, ?)");
                    $stmt->execute([$dni_estudiante, $codigo_listacot, $id_criterio, $cumplido_bool]);
                }
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Evaluaciones guardadas correctamente";
        header("Location: lista_cotejo.php?sesion=" . $sesion_seleccionada);
        exit();
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error al guardar las evaluaciones: " . $e->getMessage();
        error_log("Error en lista de cotejo: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Cotejo - IE Virgen de Fátima</title>
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
                <h1>Lista de Cotejo</h1>
                <p>Evaluación de criterios por estudiante</p>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="checklist-container">
                <div class="session-info-card">
                    <h3>Información de la Sesión</h3>
                    <div class="session-details">
                        <p><strong>Curso:</strong> <?php echo $sesion_info['curso_nombre']; ?></p>
                        <p><strong>Sesión:</strong> <?php echo $sesion_info['titulo']; ?></p>
                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($sesion_info['fecha'])); ?></p>
                        <p><strong>Lista de Cotejo:</strong> <?php echo $sesion_info['codigo_listacot'] ? 'Código ' . $sesion_info['codigo_listacot'] : 'No disponible'; ?></p>
                    </div>
                    <!-- Agregar este botón -->
                    <div class="session-actions">
                        <a href="gestion_criterios.php?sesion=<?php echo $sesion_seleccionada; ?>" class="btn-secondary">
                            <i class="fas fa-cog"></i> Gestionar Criterios
                        </a>
                    </div>
                </div>
                
                <?php if($sesion_info['codigo_listacot'] && count($criterios) > 0): ?>
                <form method="POST" id="checklistForm">
                    <input type="hidden" name="guardar_evaluaciones" value="1">
                    
                    <div class="checklist-content">
                        <div class="criteria-section">
                            <h3>Criterios de Evaluación</h3>
                            <div class="criteria-list">
                                <?php foreach($criterios as $index => $criterio): ?>
                                <div class="criterion-item">
                                    <span class="criterion-number"><?php echo $index + 1; ?></span>
                                    <span class="criterion-text"><?php echo $criterio['descripcion']; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="criteria-actions">
                                <button type="submit" class="btn-primary btn-save-all">
                                    <i class="fas fa-save"></i> Guardar Todas las Evaluaciones
                                </button>
                                <button type="button" class="btn-secondary" onclick="selectAll()">
                                    <i class="fas fa-check-double"></i> Marcar Todos
                                </button>
                                <button type="button" class="btn-secondary" onclick="unselectAll()">
                                    <i class="fas fa-times"></i> Desmarcar Todos
                                </button>
                            </div>
                        </div>
                        
                        <div class="evaluation-section">
                            <h3>Evaluación de Estudiantes</h3>
                            <div class="table-container">
                                <table class="data-table checklist-table">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Estudiante</th>
                                            <?php foreach($criterios as $index => $criterio): ?>
                                            <th title="<?php echo htmlspecialchars($criterio['descripcion']); ?>">
                                                C<?php echo $index + 1; ?>
                                                <br>
                                                <small><?php echo substr($criterio['descripcion'], 0, 20) . '...'; ?></small>
                                            </th>
                                            <?php endforeach; ?>
                                            <th>Resultado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($estudiantes as $index => $estudiante): 
                                            $total_criterios = count($criterios);
                                            $criterios_cumplidos = 0;
                                            
                                            // Calcular criterios cumplidos
                                            foreach($criterios as $criterio) {
                                                if(isset($evaluaciones_detalle[$estudiante['DNIestudiante']][$criterio['id_criterio']]) && 
                                                   $evaluaciones_detalle[$estudiante['DNIestudiante']][$criterio['id_criterio']]['cumplido']) {
                                                    $criterios_cumplidos++;
                                                }
                                            }
                                            
                                            $porcentaje = $total_criterios > 0 ? round(($criterios_cumplidos / $total_criterios) * 100) : 0;
                                        ?>
                                        <tr data-student="<?php echo $estudiante['DNIestudiante']; ?>">
                                            <td class="text-center"><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo $estudiante['apellidos'] . ', ' . $estudiante['nombres']; ?></strong>
                                                <br><small class="text-muted"><?php echo $estudiante['DNIestudiante']; ?></small>
                                            </td>
                                            <?php foreach($criterios as $criterio): 
                                                $cumplido = isset($evaluaciones_detalle[$estudiante['DNIestudiante']][$criterio['id_criterio']]) && 
                                                           $evaluaciones_detalle[$estudiante['DNIestudiante']][$criterio['id_criterio']]['cumplido'];
                                            ?>
                                            <td class="check-cell">
                                                <label class="check-label">
                                                    <input type="checkbox" 
                                                           name="evaluaciones[<?php echo $estudiante['DNIestudiante']; ?>][<?php echo $criterio['id_criterio']; ?>]"
                                                           <?php echo $cumplido ? 'checked' : ''; ?>
                                                           onchange="updateStudentResult('<?php echo $estudiante['DNIestudiante']; ?>')"
                                                           class="criterion-checkbox">
                                                    <span class="checkmark"></span>
                                                </label>
                                            </td>
                                            <?php endforeach; ?>
                                            <td class="result-cell">
                                                <div class="result-info">
                                                    <span class="result-badge <?php echo getResultClass($porcentaje); ?>">
                                                        <?php echo $porcentaje; ?>%
                                                    </span>
                                                    <div class="result-progress">
                                                        <div class="progress-bar">
                                                            <div class="progress-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                                                        </div>
                                                        <small><?php echo $criterios_cumplidos; ?>/<?php echo $total_criterios; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="action-cell">
                                                <button type="button" class="btn-icon-small btn-select-student" 
                                                        onclick="selectStudent('<?php echo $estudiante['DNIestudiante']; ?>')"
                                                        title="Marcar todos los criterios">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn-icon-small btn-unselect-student"
                                                        onclick="unselectStudent('<?php echo $estudiante['DNIestudiante']; ?>')"
                                                        title="Desmarcar todos los criterios">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Resumen estadístico -->
                            <div class="stats-summary">
                                <h4>Resumen de Evaluación</h4>
                                <div class="stats-grid-small">
                                    <?php
                                    $total_estudiantes = count($estudiantes);
                                    $estudiantes_completos = 0;
                                    $estudiantes_parciales = 0;
                                    $estudiantes_pendientes = 0;
                                    $total_criterios_evaluados = 0;
                                    $total_criterios_posibles = $total_estudiantes * count($criterios);
                                    
                                    foreach($estudiantes as $estudiante) {
                                        $criterios_cumplidos = 0;
                                        foreach($criterios as $criterio) {
                                            if(isset($evaluaciones_detalle[$estudiante['DNIestudiante']][$criterio['id_criterio']]) && 
                                               $evaluaciones_detalle[$estudiante['DNIestudiante']][$criterio['id_criterio']]['cumplido']) {
                                                $criterios_cumplidos++;
                                                $total_criterios_evaluados++;
                                            }
                                        }
                                        
                                        if($criterios_cumplidos == count($criterios)) {
                                            $estudiantes_completos++;
                                        } elseif($criterios_cumplidos > 0) {
                                            $estudiantes_parciales++;
                                        } else {
                                            $estudiantes_pendientes++;
                                        }
                                    }
                                    
                                    $porcentaje_total = $total_criterios_posibles > 0 ? round(($total_criterios_evaluados / $total_criterios_posibles) * 100) : 0;
                                    ?>
                                    
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $porcentaje_total; ?>%</div>
                                        <div class="stat-label">Progreso Total</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $estudiantes_completos; ?></div>
                                        <div class="stat-label">Completos</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $estudiantes_parciales; ?></div>
                                        <div class="stat-label">Parciales</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $estudiantes_pendientes; ?></div>
                                        <div class="stat-label">Pendientes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Lista de Cotejo No Disponible</h3>
                    <p>No se ha creado una lista de cotejo para esta sesión.</p>
                    <a href="cursos.php" class="btn-primary">Volver a Cursos</a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="js/script.js"></script>
    <script>
    // Función para actualizar el resultado de un estudiante
    function updateStudentResult(studentDni) {
        const row = document.querySelector(`tr[data-student="${studentDni}"]`);
        const checkboxes = row.querySelectorAll('.criterion-checkbox');
        const resultBadge = row.querySelector('.result-badge');
        const progressFill = row.querySelector('.progress-fill');
        const progressText = row.querySelector('.result-progress small');
        
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const totalCount = checkboxes.length;
        const percentage = totalCount > 0 ? Math.round((checkedCount / totalCount) * 100) : 0;
        
        // Actualizar elementos visuales
        resultBadge.textContent = percentage + '%';
        resultBadge.className = 'result-badge ' + getResultClass(percentage);
        progressFill.style.width = percentage + '%';
        progressText.textContent = checkedCount + '/' + totalCount;
        
        // Actualizar estadísticas globales
        updateGlobalStats();
    }
    
    // Función para obtener la clase CSS según el porcentaje
    function getResultClass(percentage) {
        if (percentage >= 90) return 'result-complete';
        if (percentage >= 50) return 'result-partial';
        return 'result-incomplete';
    }
    
    // Función para marcar todos los criterios de un estudiante
    function selectStudent(studentDni) {
        const row = document.querySelector(`tr[data-student="${studentDni}"]`);
        const checkboxes = row.querySelectorAll('.criterion-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateStudentResult(studentDni);
    }
    
    // Función para desmarcar todos los criterios de un estudiante
    function unselectStudent(studentDni) {
        const row = document.querySelector(`tr[data-student="${studentDni}"]`);
        const checkboxes = row.querySelectorAll('.criterion-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateStudentResult(studentDni);
    }
    
    // Función para marcar todos los criterios de todos los estudiantes
    function selectAll() {
        const checkboxes = document.querySelectorAll('.criterion-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        // Actualizar todos los resultados
        const studentRows = document.querySelectorAll('tr[data-student]');
        studentRows.forEach(row => {
            const studentDni = row.getAttribute('data-student');
            updateStudentResult(studentDni);
        });
    }
    
    // Función para desmarcar todos los criterios de todos los estudiantes
    function unselectAll() {
        const checkboxes = document.querySelectorAll('.criterion-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        // Actualizar todos los resultados
        const studentRows = document.querySelectorAll('tr[data-student]');
        studentRows.forEach(row => {
            const studentDni = row.getAttribute('data-student');
            updateStudentResult(studentDni);
        });
    }
    
    // Función para actualizar estadísticas globales (se puede expandir)
    function updateGlobalStats() {
        // Aquí se pueden agregar cálculos en tiempo real si es necesario
        console.log('Estadísticas actualizadas');
    }
    
    // Inicializar todos los resultados al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const studentRows = document.querySelectorAll('tr[data-student]');
        studentRows.forEach(row => {
            const studentDni = row.getAttribute('data-student');
            updateStudentResult(studentDni);
        });
        
        // Prevenir envío duplicado del formulario
        const form = document.getElementById('checklistForm');
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            
            isSubmitting = true;
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
        });
    });
    </script>
</body>
</html>

<?php
// Función auxiliar para determinar la clase del resultado
function getResultClass($porcentaje) {
    if ($porcentaje >= 90) return 'result-complete';
    if ($porcentaje >= 50) return 'result-partial';
    return 'result-incomplete';
}
?>