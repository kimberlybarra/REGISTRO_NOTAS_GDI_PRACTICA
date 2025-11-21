<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

// Obtener información del docente
$stmt = $pdo->prepare("SELECT * FROM docente WHERE DNIdocente = ?");
$stmt->execute([$_SESSION['docente_id']]);
$docente = $stmt->fetch();

// Obtener cantidad de cursos
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM curso WHERE DNIdocente = ?");
$stmt->execute([$_SESSION['docente_id']]);
$total_cursos = $stmt->fetch()['total'];

// Obtener cantidad de estudiantes del grado del docente
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM estudiante WHERE id_grado_seccion = 1");
$stmt->execute();
$total_estudiantes = $stmt->fetch()['total'];

// Obtener cantidad de sesiones del docente
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sesion_de_aprendizaje sa 
                      JOIN curso c ON sa.codigo_curso = c.codigo_curso 
                      WHERE c.DNIdocente = ?");
$stmt->execute([$_SESSION['docente_id']]);
$total_sesiones = $stmt->fetch()['total'];

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

// Calcular promedio general REAL del docente
$stmt = $pdo->prepare("SELECT AVG(
                         CASE 
                           WHEN n.nota_final = 'AD' THEN 4
                           WHEN n.nota_final = 'A' THEN 3
                           WHEN n.nota_final = 'B' THEN 2
                           WHEN n.nota_final = 'C' THEN 1
                           ELSE NULL
                         END
                       ) as promedio 
                       FROM notas n
                       JOIN sesion_de_aprendizaje sa ON n.codigo_sesion = sa.codigo_sesion
                       JOIN curso c ON sa.codigo_curso = c.codigo_curso
                       WHERE c.DNIdocente = ? AND n.nota_final IS NOT NULL");
$stmt->execute([$_SESSION['docente_id']]);
$promedio_result = $stmt->fetch();
$promedio_general = $promedio_result['promedio'] ? number_format($promedio_result['promedio'], 1) : '0.0';

// Obtener cursos recientes del docente
$stmt = $pdo->prepare("SELECT c.*, 
                      (SELECT COUNT(*) FROM sesion_de_aprendizaje sa WHERE sa.codigo_curso = c.codigo_curso) as total_sesiones,
                      (SELECT COUNT(DISTINCT n.DNIestudiante) FROM notas n 
                       JOIN sesion_de_aprendizaje sa ON n.codigo_sesion = sa.codigo_sesion 
                       WHERE sa.codigo_curso = c.codigo_curso) as estudiantes_calificados
                      FROM curso c 
                      WHERE c.DNIdocente = ? 
                      ORDER BY c.codigo_curso DESC 
                      LIMIT 4");
$stmt->execute([$_SESSION['docente_id']]);
$cursos_recientes = $stmt->fetchAll();

// Obtener sesiones recientes
$stmt = $pdo->prepare("SELECT sa.*, c.nombre as curso_nombre 
                      FROM sesion_de_aprendizaje sa 
                      JOIN curso c ON sa.codigo_curso = c.codigo_curso 
                      WHERE c.DNIdocente = ? 
                      ORDER BY sa.fecha DESC, sa.codigo_sesion DESC 
                      LIMIT 5");
$stmt->execute([$_SESSION['docente_id']]);
$sesiones_recientes = $stmt->fetchAll();

// Obtener distribución de notas para el gráfico
$stmt = $pdo->prepare("SELECT n.nota_final, COUNT(*) as cantidad 
                      FROM notas n
                      JOIN sesion_de_aprendizaje sa ON n.codigo_sesion = sa.codigo_sesion
                      JOIN curso c ON sa.codigo_curso = c.codigo_curso
                      WHERE c.DNIdocente = ? AND n.nota_final IS NOT NULL
                      GROUP BY n.nota_final
                      ORDER BY 
                        CASE n.nota_final
                          WHEN 'AD' THEN 1
                          WHEN 'A' THEN 2
                          WHEN 'B' THEN 3
                          WHEN 'C' THEN 4
                          ELSE 5
                        END");
$stmt->execute([$_SESSION['docente_id']]);
$distribucion_notas = $stmt->fetchAll();

// Calcular total de notas para porcentajes
$total_notas = 0;
foreach($distribucion_notas as $distribucion) {
    $total_notas += $distribucion['cantidad'];
}

// Obtener actividades recientes (combinación de sesiones y calificaciones)
$actividades_recientes = [];

// Agregar sesiones recientes como actividades
foreach($sesiones_recientes as $sesion) {
    $actividades_recientes[] = [
        'tipo' => 'sesion',
        'icono' => 'fa-chalkboard-teacher',
        'color' => 'var(--primary-color)',
        'mensaje' => 'Nueva sesión: ' . $sesion['titulo'],
        'detalle' => $sesion['curso_nombre'],
        'fecha' => $sesion['fecha'],
        'tiempo' => calcularTiempoRelativo($sesion['fecha'])
    ];
}

// Agregar calificaciones recientes (últimas notas registradas)
$stmt = $pdo->prepare("SELECT n.*, sa.titulo as sesion_titulo, c.nombre as curso_nombre, 
                      e.nombres as estudiante_nombres, e.apellidos as estudiante_apellidos
                      FROM notas n
                      JOIN sesion_de_aprendizaje sa ON n.codigo_sesion = sa.codigo_sesion
                      JOIN curso c ON sa.codigo_curso = c.codigo_curso
                      JOIN estudiante e ON n.DNIestudiante = e.DNIestudiante
                      WHERE c.DNIdocente = ? 
                      ORDER BY n.codigo_sesion DESC 
                      LIMIT 3");
$stmt->execute([$_SESSION['docente_id']]);
$calificaciones_recientes = $stmt->fetchAll();

foreach($calificaciones_recientes as $calificacion) {
    $actividades_recientes[] = [
        'tipo' => 'calificacion',
        'icono' => 'fa-check-circle',
        'color' => 'var(--success-color)',
        'mensaje' => 'Calificación registrada: ' . $calificacion['estudiante_nombres'] . ' ' . $calificacion['estudiante_apellidos'],
        'detalle' => $calificacion['curso_nombre'] . ' - ' . $calificacion['nota_final'],
        'fecha' => date('Y-m-d'), // Usar fecha actual como aproximación
        'tiempo' => 'Recientemente'
    ];
}

// Ordenar actividades por fecha
usort($actividades_recientes, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// Limitar a 5 actividades
$actividades_recientes = array_slice($actividades_recientes, 0, 5);

// Función para calcular tiempo relativo
function calcularTiempoRelativo($fecha) {
    $ahora = new DateTime();
    $fechaObj = new DateTime($fecha);
    $diferencia = $ahora->diff($fechaObj);
    
    if ($diferencia->y > 0) return 'Hace ' . $diferencia->y . ' año' . ($diferencia->y > 1 ? 's' : '');
    if ($diferencia->m > 0) return 'Hace ' . $diferencia->m . ' mes' . ($diferencia->m > 1 ? 'es' : '');
    if ($diferencia->d > 0) return 'Hace ' . $diferencia->d . ' día' . ($diferencia->d > 1 ? 's' : '');
    if ($diferencia->h > 0) return 'Hace ' . $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '');
    if ($diferencia->i > 0) return 'Hace ' . $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '');
    return 'Hace unos momentos';
}

// Función para obtener clase de nota
function getNotaClass($nota) {
    if($nota == 'AD') return 'nota-excelente';
    if($nota == 'A') return 'nota-buena';
    if($nota == 'B') return 'nota-regular';
    if($nota == 'C') return 'nota-baja';
    return 'nota-sin-calificar';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - IE Virgen de Fátima</title>
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
                <h1>Inicio</h1>
                <p>Bienvenido, <?php echo $docente['nombres'] . ' ' . $docente['apellidos']; ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_cursos; ?></h3>
                        <p>Cursos Asignados</p>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Activos</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_estudiantes; ?></h3>
                        <p>Estudiantes</p>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-user-graduate"></i>
                        <span>6° Sección B</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_sesiones; ?></h3>
                        <p>Sesiones de Aprendizaje</p>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line"></i>
                        <span>Total registradas</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $promedio_general; ?></h3>
                        <p>Promedio General</p>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-star"></i>
                        <span>Basado en <?php echo $total_notas; ?> notas</span>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-content">
                <div class="content-row">
                    <div class="content-col">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3>Distribución de Calificaciones</h3>
                                <span class="card-badge">Actualizado</span>
                            </div>
                            <div class="card-body">
                                <?php if($total_notas > 0): ?>
                                    <div class="distribution-chart">
                                        <?php foreach($distribucion_notas as $distribucion): 
                                            $porcentaje = $total_notas > 0 ? round(($distribucion['cantidad'] / $total_notas) * 100) : 0;
                                        ?>
                                        <div class="distribution-item">
                                            <div class="distribution-label">
                                                <span class="nota-badge <?php echo getNotaClass($distribucion['nota_final']); ?>">
                                                    <?php echo $distribucion['nota_final']; ?>
                                                </span>
                                                <span class="distribution-count">
                                                    <?php echo $distribucion['cantidad']; ?> 
                                                    (<?php echo $porcentaje; ?>%)
                                                </span>
                                            </div>
                                            <div class="distribution-bar">
                                                <div class="distribution-fill" 
                                                     style="width: <?php echo $porcentaje; ?>%;
                                                            background: <?php 
                                                                if($distribucion['nota_final'] == 'AD') echo '#28a745';
                                                                elseif($distribucion['nota_final'] == 'A') echo '#17a2b8';
                                                                elseif($distribucion['nota_final'] == 'B') echo '#ffc107';
                                                                else echo '#dc3545';
                                                            ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="distribution-total">
                                        <small>Total de calificaciones: <?php echo $total_notas; ?></small>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state-small">
                                        <i class="fas fa-chart-pie"></i>
                                        <p>No hay calificaciones registradas</p>
                                        <a href="cursos.php" class="btn-primary btn-sm">Registrar Notas</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-col">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3>Mis Cursos</h3>
                                <a href="cursos.php" class="card-link">Ver todos</a>
                            </div>
                            <div class="card-body">
                                <?php if(count($cursos_recientes) > 0): ?>
                                    <div class="courses-list">
                                        <?php foreach($cursos_recientes as $curso): ?>
                                        <div class="course-item">
                                            <div class="course-icon">
                                                <i class="fas fa-book-open"></i>
                                            </div>
                                            <div class="course-details">
                                                <h4><?php echo $curso['nombre']; ?></h4>
                                                <p><?php echo $curso['total_sesiones']; ?> sesiones</p>
                                                <small>
                                                    <?php echo $curso['estudiantes_calificados']; ?> estudiantes calificados
                                                </small>
                                            </div>
                                            <div class="course-action">
                                                <a href="reportes.php?curso=<?php echo $curso['codigo_curso']; ?>" class="btn-icon-small">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state-small">
                                        <i class="fas fa-book"></i>
                                        <p>No tienes cursos asignados</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="content-row">
                    <div class="content-col-full">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3>Actividades Recientes</h3>
                                <span class="card-badge">Últimas actividades</span>
                            </div>
                            <div class="card-body">
                                <?php if(count($actividades_recientes) > 0): ?>
                                    <div class="activities-timeline">
                                        <?php foreach($actividades_recientes as $actividad): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker" style="background: <?php echo $actividad['color']; ?>">
                                                <i class="fas <?php echo $actividad['icono']; ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="timeline-header">
                                                    <h4><?php echo $actividad['mensaje']; ?></h4>
                                                    <span class="timeline-date"><?php echo $actividad['tiempo']; ?></span>
                                                </div>
                                                <p class="timeline-description">
                                                    <?php echo $actividad['detalle']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state-small">
                                        <i class="fas fa-calendar-alt"></i>
                                        <p>No hay actividades recientes</p>
                                        <a href="cursos.php" class="btn-primary btn-sm">Comenzar a trabajar</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="content-row">
                    <div class="content-col">
                        <div class="dashboard-card quick-actions">
                            <div class="card-header">
                                <h3>Acciones Rápidas</h3>
                            </div>
                            <div class="card-body">
                                <div class="actions-grid">
                                    <a href="cursos.php" class="action-item">
                                        <div class="action-icon">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>Gestionar Cursos</h4>
                                            <p>Administrar cursos y sesiones</p>
                                        </div>
                                        <div class="action-arrow">
                                            <i class="fas fa-chevron-right"></i>
                                        </div>
                                    </a>
                                    
                                    <a href="registrar_notas.php" class="action-item">
                                        <div class="action-icon">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>Registrar Notas</h4>
                                            <p>Ingresar calificaciones</p>
                                        </div>
                                        <div class="action-arrow">
                                            <i class="fas fa-chevron-right"></i>
                                        </div>
                                    </a>
                                    
                                    <a href="reportes_completos.php" class="action-item">
                                        <div class="action-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>Reporte Completo</h4>
                                            <p>Ver todas las calificaciones</p>
                                        </div>
                                        <div class="action-arrow">
                                            <i class="fas fa-chevron-right"></i>
                                        </div>
                                    </a>
                                    
                                    <a href="estudiantes.php" class="action-item">
                                        <div class="action-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="action-content">
                                            <h4>Lista de Estudiantes</h4>
                                            <p>Gestionar estudiantes</p>
                                        </div>
                                        <div class="action-arrow">
                                            <i class="fas fa-chevron-right"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-col">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3>Próximas Actividades</h3>
                            </div>
                            <div class="card-body">
                                <div class="upcoming-events">
                                    <div class="event-item">
                                        <div class="event-date">
                                            <span class="event-day">15</span>
                                            <span class="event-month">MAR</span>
                                        </div>
                                        <div class="event-details">
                                            <h4>Reunión de Padres</h4>
                                            <p>Entrega de libretas - 3:00 PM</p>
                                        </div>
                                    </div>
                                    
                                    <div class="event-item">
                                        <div class="event-date">
                                            <span class="event-day">20</span>
                                            <span class="event-month">MAR</span>
                                        </div>
                                        <div class="event-details">
                                            <h4>Evaluación Bimestral</h4>
                                            <p>Matemática - Todo el día</p>
                                        </div>
                                    </div>
                                    
                                    <div class="event-item">
                                        <div class="event-date">
                                            <span class="event-day">25</span>
                                            <span class="event-month">MAR</span>
                                        </div>
                                        <div class="event-details">
                                            <h4>Capacitación Docente</h4>
                                            <p>Nuevas metodologías - 9:00 AM</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>