<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

// Obtener cursos del docente
$stmt = $pdo->prepare("SELECT * FROM curso WHERE DNIdocente = ?");
$stmt->execute([$_SESSION['docente_id']]);
$cursos = $stmt->fetchAll();

// Procesar formulario de nueva sesión
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['agregar_sesion'])) {
        $codigo_curso = $_POST['codigo_curso'];
        $codigo_sesion = $_POST['codigo_sesion'];
        $fecha = $_POST['fecha'];
        $duracion = $_POST['duracion'];
        $titulo = $_POST['titulo'];
        $evidencia = $_POST['evidencia'];
        $competencia = $_POST['competencia'];
        
        try {
            // Verificar que el código de sesión no exista
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sesion_de_aprendizaje WHERE codigo_sesion = ?");
            $stmt->execute([$codigo_sesion]);
            $existe_sesion = $stmt->fetchColumn();
            
            if ($existe_sesion > 0) {
                $_SESSION['error'] = "El código de sesión ya existe. Por favor, use otro código.";
                header("Location: cursos.php");
                exit();
            }
            
            // Insertar sesión de aprendizaje
            $stmt = $pdo->prepare("INSERT INTO sesion_de_aprendizaje (codigo_sesion, fecha, duracion, titulo, evidencia_aprendizaje, codigo_curso, id_grado_seccion) 
                                  VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$codigo_sesion, $fecha, $duracion, $titulo, $evidencia, $codigo_curso]);
            
            // Insertar competencia
            $stmt = $pdo->prepare("INSERT INTO competencia (descripcion, codigo_sesion) VALUES (?, ?)");
            $stmt->execute([$competencia, $codigo_sesion]);
            
            // Crear lista de cotejo automáticamente
            $codigo_listacot = $codigo_sesion + 1000;
            $stmt = $pdo->prepare("INSERT INTO lista_de_cotejo (codigo_listacot, proposito, codigo_sesion, id_grado_seccion) 
                                  VALUES (?, ?, ?, 1)");
            $stmt->execute([$codigo_listacot, "Evaluación de la sesión: $titulo", $codigo_sesion]);
            
            // Agregar criterios por defecto
            $criterios = [
                "Demuestra comprensión del tema",
                "Participa activamente en clase",
                "Realiza las actividades propuestas",
                "Muestra organización en su trabajo"
            ];
            
            foreach($criterios as $criterio) {
                $stmt = $pdo->prepare("INSERT INTO criterio (descripcion, codigo_listacot) VALUES (?, ?)");
                $stmt->execute([$criterio, $codigo_listacot]);
            }
            
            // Crear evaluaciones para todos los estudiantes
            $stmt = $pdo->prepare("INSERT INTO evaluacion (DNIestudiante, codigo_listacot) 
                                  SELECT DNIestudiante, ? FROM estudiante WHERE id_grado_seccion = 1");
            $stmt->execute([$codigo_listacot]);
            
            $_SESSION['success'] = "Sesión agregada correctamente al curso";
            header("Location: cursos.php");
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error al agregar sesión: " . $e->getMessage();
        }
    }
    
    // Procesar formulario de nuevo examen
    if(isset($_POST['agregar_examen'])) {
        $codigo_curso = $_POST['codigo_curso_examen'];
        $codigo_examen = $_POST['codigo_examen'];
        $tipo_examen = $_POST['tipo_examen'];
        $fecha_examen = $_POST['fecha_examen'];
        $descripcion = $_POST['descripcion_examen'];
        $peso = $_POST['peso_examen'];
        
        try {
            // Verificar que el código de examen no exista
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM examen WHERE codigo_examen = ?");
            $stmt->execute([$codigo_examen]);
            $existe_examen = $stmt->fetchColumn();
            
            if ($existe_examen > 0) {
                $_SESSION['error'] = "El código de examen ya existe. Por favor, use otro código.";
                header("Location: cursos.php");
                exit();
            }
            
            // Insertar examen
            $stmt = $pdo->prepare("INSERT INTO examen (codigo_examen, codigo_curso, tipo_examen, fecha, descripcion, peso) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo_examen, $codigo_curso, $tipo_examen, $fecha_examen, $descripcion, $peso]);
            
            $_SESSION['success'] = "Examen agregado correctamente al curso";
            header("Location: cursos.php");
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error al agregar examen: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos - Institución Nacional</title>
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
                <h1>Mis Cursos</h1>
                <p>Gestiona tus cursos asignados, sesiones y exámenes</p>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="courses-grid">
                <?php foreach($cursos as $curso): 
                    // Obtener sesiones del curso
                    $stmt = $pdo->prepare("SELECT * FROM sesion_de_aprendizaje WHERE codigo_curso = ? ORDER BY fecha DESC");
                    $stmt->execute([$curso['codigo_curso']]);
                    $sesiones = $stmt->fetchAll();
                    
                    // Obtener exámenes del curso
                    $stmt = $pdo->prepare("SELECT * FROM examen WHERE codigo_curso = ? ORDER BY fecha DESC");
                    $stmt->execute([$curso['codigo_curso']]);
                    $examenes = $stmt->fetchAll();
                ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3><?php echo $curso['nombre']; ?></h3>
                        <span class="course-code">Código: <?php echo $curso['codigo_curso']; ?></span>
                    </div>
                    <div class="course-info">
                        <p><i class="fas fa-chalkboard-teacher"></i> Docente: <?php echo $_SESSION['docente_nombres'] . ' ' . $_SESSION['docente_apellidos']; ?></p>
                        <p><i class="fas fa-users"></i> Sección: 6° B</p>
                        <p><i class="fas fa-calendar-alt"></i> Sesiones: <?php echo count($sesiones); ?></p>
                        <p><i class="fas fa-file-alt"></i> Exámenes: <?php echo count($examenes); ?></p>
                    </div>
                    
                    <!-- Lista de sesiones -->
                    <div class="course-sessions">
                        <h4>Sesiones de Aprendizaje:</h4>
                        <?php if(count($sesiones) > 0): ?>
                            <div class="sessions-list">
                                <?php foreach($sesiones as $sesion): 
                                    $stmt = $pdo->prepare("SELECT * FROM competencia WHERE codigo_sesion = ?");
                                    $stmt->execute([$sesion['codigo_sesion']]);
                                    $competencia = $stmt->fetch();
                                ?>
                                <div class="session-item">
                                    <div class="session-info">
                                        <strong><?php echo $sesion['titulo']; ?></strong>
                                        <span class="session-date"><?php echo date('d/m/Y', strtotime($sesion['fecha'])); ?></span>
                                    </div>
                                    <div class="session-actions">
                                        <a href="registrar_notas.php?sesion=<?php echo $sesion['codigo_sesion']; ?>" class="btn-icon-small" title="Registrar notas">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="lista_cotejo.php?sesion=<?php echo $sesion['codigo_sesion']; ?>" class="btn-icon-small" title="Ver lista de cotejo">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-sessions">No hay sesiones registradas</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Lista de exámenes -->
                    <div class="course-exams">
                        <h4>Exámenes:</h4>
                        <?php if(count($examenes) > 0): ?>
                            <div class="exams-list">
                                <?php foreach($examenes as $examen): ?>
                                <div class="exam-item">
                                    <div class="exam-info">
                                        <strong><?php echo $examen['tipo_examen']; ?></strong>
                                        <span class="exam-date"><?php echo date('d/m/Y', strtotime($examen['fecha'])); ?></span>
                                        <small class="exam-desc"><?php echo $examen['descripcion']; ?></small>
                                    </div>
                                    <div class="exam-actions">
                                        <a href="registrar_examen.php?examen=<?php echo $examen['codigo_examen']; ?>" class="btn-icon-small" title="Registrar notas de examen">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-exams">No hay exámenes registrados</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="course-actions">
                        <button class="btn-secondary btn-sm btn-add-session" 
                                data-course="<?php echo $curso['codigo_curso']; ?>"
                                data-course-name="<?php echo htmlspecialchars($curso['nombre']); ?>">
                            <i class="fas fa-plus"></i> Sesión
                        </button>
                        <button class="btn-secondary btn-sm btn-add-exam" 
                                data-course="<?php echo $curso['codigo_curso']; ?>"
                                data-course-name="<?php echo htmlspecialchars($curso['nombre']); ?>">
                            <i class="fas fa-file-alt"></i> Examen
                        </button>
                        <a href="registrar_notas.php?curso=<?php echo $curso['codigo_curso']; ?>" class="btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Notas Sesiones
                        </a>
                        <a href="registrar_examen.php?curso=<?php echo $curso['codigo_curso']; ?>" class="btn-primary btn-sm">
                            <i class="fas fa-file-signature"></i> Notas Exámenes
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal para agregar sesión -->
    <div id="sessionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Agregar Nueva Sesión</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" class="modal-form" id="sessionForm">
                <input type="hidden" name="codigo_curso" id="modal_course">
                <input type="hidden" name="agregar_sesion" value="1">
                
                <div class="form-group">
                    <label for="codigo_sesion">Código de Sesión:</label>
                    <input type="number" id="codigo_sesion" name="codigo_sesion" required 
                           min="2001" max="2999" placeholder="Ej: 2007, 2008...">
                    <small class="form-help">Use un código único entre 2001-2999</small>
                </div>
                
                <div class="form-group">
                    <label for="titulo">Título de la Sesión:</label>
                    <input type="text" id="titulo" name="titulo" required 
                           placeholder="Ej: Resolución de problemas con fracciones">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha">Fecha:</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="duracion">Duración (minutos):</label>
                        <input type="number" id="duracion" name="duracion" required 
                               min="30" max="180" placeholder="90">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="evidencia">Evidencia de Aprendizaje:</label>
                    <input type="text" id="evidencia" name="evidencia" required 
                           placeholder="Ej: Ficha de ejercicios resueltos">
                </div>
                
                <div class="form-group">
                    <label for="competencia">Competencia:</label>
                    <textarea id="competencia" name="competencia" rows="3" required 
                              placeholder="Describa la competencia a desarrollar..."></textarea>
                </div>
                
                <div class="form-info">
                    <p><strong>Curso seleccionado:</strong> <span id="selectedCourse">-</span></p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Sesión
                    </button>
                    <button type="button" class="btn-secondary close-modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para agregar examen -->
    <div id="examModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="examModalTitle">Agregar Nuevo Examen</h3>
                <span class="close-exam">&times;</span>
            </div>
            <form method="POST" class="modal-form" id="examForm">
                <input type="hidden" name="codigo_curso_examen" id="modal_course_examen">
                <input type="hidden" name="agregar_examen" value="1">
                
                <div class="form-group">
                    <label for="codigo_examen">Código de Examen:</label>
                    <input type="number" id="codigo_examen" name="codigo_examen" required 
                           min="4001" max="4999" placeholder="Ej: 4001, 4002...">
                    <small class="form-help">Use un código único entre 4001-4999</small>
                </div>
                
                <div class="form-group">
                    <label for="tipo_examen">Tipo de Examen:</label>
                    <select id="tipo_examen" name="tipo_examen" required>
                        <option value="">-- Seleccionar tipo --</option>
                        <option value="BIMESTRAL">Bimestral</option>
                        <option value="RECUPERACIÓN">Recuperación</option>
                        <option value="SUSTITUTORIO">Sustitutorio</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha_examen">Fecha del Examen:</label>
                    <input type="date" id="fecha_examen" name="fecha_examen" required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion_examen">Descripción:</label>
                    <input type="text" id="descripcion_examen" name="descripcion_examen" required 
                           placeholder="Ej: Examen del primer bimestre">
                </div>
                
                <div class="form-group">
                    <label for="peso_examen">Peso en el Promedio (%):</label>
                    <input type="number" id="peso_examen" name="peso_examen" required 
                        min="10" max="50" step="0.01" value="30.00">
                    <small class="form-help">Porcentaje que representa en el promedio final (10-50%) - Ej: 30.00</small>
                </div>
                
                <div class="form-info">
                    <p><strong>Curso seleccionado:</strong> <span id="selectedCourseExam">-</span></p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Examen
                    </button>
                    <button type="button" class="btn-secondary close-exam-modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/script.js"></script>
    <script>
    // Modal functionality para sesiones
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de sesiones
        const sessionModal = document.getElementById('sessionModal');
        const btnAddSession = document.querySelectorAll('.btn-add-session');
        const closeBtn = document.querySelector('.close');
        const closeModalBtn = document.querySelector('.close-modal');
        
        // Modal de exámenes
        const examModal = document.getElementById('examModal');
        const btnAddExam = document.querySelectorAll('.btn-add-exam');
        const closeExamBtn = document.querySelector('.close-exam');
        const closeExamModalBtn = document.querySelector('.close-exam-modal');
        
        // Funcionalidad para sesiones
        btnAddSession.forEach(btn => {
            btn.addEventListener('click', function() {
                const courseId = this.getAttribute('data-course');
                const courseName = this.getAttribute('data-course-name');
                
                document.getElementById('modal_course').value = courseId;
                document.getElementById('selectedCourse').textContent = courseName;
                document.getElementById('modalTitle').textContent = `Agregar Sesión - ${courseName}`;
                
                // Generar código de sesión sugerido
                const suggestedCode = generateSuggestedCode('session');
                document.getElementById('codigo_sesion').value = suggestedCode;
                
                // Limpiar y resetear el formulario
                document.getElementById('sessionForm').reset();
                document.getElementById('fecha').valueAsDate = new Date();
                document.getElementById('duracion').value = '90';
                
                sessionModal.style.display = 'block';
            });
        });
        
        // Funcionalidad para exámenes
        btnAddExam.forEach(btn => {
            btn.addEventListener('click', function() {
                const courseId = this.getAttribute('data-course');
                const courseName = this.getAttribute('data-course-name');
                
                document.getElementById('modal_course_examen').value = courseId;
                document.getElementById('selectedCourseExam').textContent = courseName;
                document.getElementById('examModalTitle').textContent = `Agregar Examen - ${courseName}`;
                
                // Generar código de examen sugerido
                const suggestedCode = generateSuggestedCode('exam');
                document.getElementById('codigo_examen').value = suggestedCode;
                
                // Limpiar y resetear el formulario
                document.getElementById('examForm').reset();
                document.getElementById('fecha_examen').valueAsDate = new Date();
                document.getElementById('peso_examen').value = '30.00'; // CAMBIO AQUÍ
                
                examModal.style.display = 'block';
            });
        });
        
        // Cerrar modales
        closeBtn.addEventListener('click', function() {
            sessionModal.style.display = 'none';
        });
        
        closeModalBtn.addEventListener('click', function() {
            sessionModal.style.display = 'none';
        });
        
        closeExamBtn.addEventListener('click', function() {
            examModal.style.display = 'none';
        });
        
        closeExamModalBtn.addEventListener('click', function() {
            examModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target == sessionModal) {
                sessionModal.style.display = 'none';
            }
            if (event.target == examModal) {
                examModal.style.display = 'none';
            }
        });
        
        // Generar código sugerido
        function generateSuggestedCode(type) {
            const now = new Date();
            if (type === 'session') {
                const baseCode = 2000;
                const month = now.getMonth() + 1;
                const day = now.getDate();
                return baseCode + month * 10 + day;
            } else {
                const baseCode = 4000;
                const month = now.getMonth() + 1;
                const day = now.getDate();
                return baseCode + month * 10 + day;
            }
        }
        
        // Set today's date as default
        document.getElementById('fecha').valueAsDate = new Date();
        document.getElementById('fecha_examen').valueAsDate = new Date();
    });
    </script>
</body>
</html>