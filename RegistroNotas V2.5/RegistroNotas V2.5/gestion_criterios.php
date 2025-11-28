<?php
session_start();
if(!isset($_SESSION['docente_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/database.php';

$sesion_seleccionada = isset($_GET['sesion']) ? $_GET['sesion'] : null;
$codigo_listacot = isset($_GET['lista']) ? $_GET['lista'] : null;

if(!$sesion_seleccionada && !$codigo_listacot) {
    header("Location: cursos.php");
    exit();
}

// Obtener información de la sesión y lista de cotejo
if($sesion_seleccionada) {
    $stmt = $pdo->prepare("SELECT sa.*, c.nombre as curso_nombre, lc.codigo_listacot 
                          FROM sesion_de_aprendizaje sa 
                          JOIN curso c ON sa.codigo_curso = c.codigo_curso 
                          LEFT JOIN lista_de_cotejo lc ON sa.codigo_sesion = lc.codigo_sesion 
                          WHERE sa.codigo_sesion = ?");
    $stmt->execute([$sesion_seleccionada]);
    $sesion_info = $stmt->fetch();
    $codigo_listacot = $sesion_info['codigo_listacot'];
} else {
    $stmt = $pdo->prepare("SELECT lc.*, sa.titulo, sa.codigo_sesion, c.nombre as curso_nombre 
                          FROM lista_de_cotejo lc 
                          JOIN sesion_de_aprendizaje sa ON lc.codigo_sesion = sa.codigo_sesion 
                          JOIN curso c ON sa.codigo_curso = c.codigo_curso 
                          WHERE lc.codigo_listacot = ?");
    $stmt->execute([$codigo_listacot]);
    $sesion_info = $stmt->fetch();
    $sesion_seleccionada = $sesion_info['codigo_sesion'];
}

// Obtener criterios activos
$stmt = $pdo->prepare("SELECT * FROM criterio WHERE codigo_listacot = ? AND activo = TRUE ORDER BY orden, id_criterio");
$stmt->execute([$codigo_listacot]);
$criterios_activos = $stmt->fetchAll();

// Obtener criterios inactivos
$stmt = $pdo->prepare("SELECT * FROM criterio WHERE codigo_listacot = ? AND activo = FALSE ORDER BY orden, id_criterio");
$stmt->execute([$codigo_listacot]);
$criterios_inactivos = $stmt->fetchAll();

// Procesar agregar nuevo criterio
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_criterio'])) {
    $descripcion = trim($_POST['descripcion']);
    $orden = $_POST['orden'] ?? 0;
    
    if(!empty($descripcion)) {
        try {
            // Obtener el máximo orden actual
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 as nuevo_orden FROM criterio WHERE codigo_listacot = ?");
            $stmt->execute([$codigo_listacot]);
            $nuevo_orden = $stmt->fetch()['nuevo_orden'];
            
            $stmt = $pdo->prepare("INSERT INTO criterio (descripcion, codigo_listacot, orden, activo) VALUES (?, ?, ?, TRUE)");
            $stmt->execute([$descripcion, $codigo_listacot, $nuevo_orden]);
            
            $_SESSION['success'] = "Criterio agregado correctamente";
            header("Location: gestion_criterios.php?sesion=" . $sesion_seleccionada);
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error al agregar criterio: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "La descripción del criterio no puede estar vacía";
    }
}

// Procesar editar criterio
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_criterio'])) {
    $id_criterio = $_POST['id_criterio'];
    $descripcion = trim($_POST['descripcion']);
    $orden = $_POST['orden'];
    
    if(!empty($descripcion)) {
        try {
            $stmt = $pdo->prepare("UPDATE criterio SET descripcion = ?, orden = ? WHERE id_criterio = ?");
            $stmt->execute([$descripcion, $orden, $id_criterio]);
            
            $_SESSION['success'] = "Criterio actualizado correctamente";
            header("Location: gestion_criterios.php?sesion=" . $sesion_seleccionada);
            exit();
            
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error al actualizar criterio: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "La descripción del criterio no puede estar vacía";
    }
}

// Procesar eliminar/desactivar criterio
if(isset($_GET['accion']) && isset($_GET['id'])) {
    $id_criterio = $_GET['id'];
    $accion = $_GET['accion'];
    
    try {
        if($accion == 'desactivar') {
            $stmt = $pdo->prepare("UPDATE criterio SET activo = FALSE WHERE id_criterio = ?");
            $stmt->execute([$id_criterio]);
            $_SESSION['success'] = "Criterio desactivado correctamente";
        } 
        elseif($accion == 'activar') {
            $stmt = $pdo->prepare("UPDATE criterio SET activo = TRUE WHERE id_criterio = ?");
            $stmt->execute([$id_criterio]);
            $_SESSION['success'] = "Criterio activado correctamente";
        }
        elseif($accion == 'eliminar') {
            // Verificar si hay evaluaciones asociadas
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM evaluacion_detalle WHERE id_criterio = ?");
            $stmt->execute([$id_criterio]);
            $tiene_evaluaciones = $stmt->fetchColumn();
            
            if($tiene_evaluaciones > 0) {
                $_SESSION['error'] = "No se puede eliminar el criterio porque tiene evaluaciones asociadas. Puede desactivarlo en su lugar.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM criterio WHERE id_criterio = ?");
                $stmt->execute([$id_criterio]);
                $_SESSION['success'] = "Criterio eliminado correctamente";
            }
        }
        elseif($accion == 'subir') {
            // Reordenar criterios
            $stmt = $pdo->prepare("SELECT orden FROM criterio WHERE id_criterio = ?");
            $stmt->execute([$id_criterio]);
            $orden_actual = $stmt->fetch()['orden'];
            
            if($orden_actual > 1) {
                // Intercambiar con el criterio anterior
                $stmt = $pdo->prepare("UPDATE criterio SET orden = CASE 
                                    WHEN id_criterio = ? THEN orden - 1 
                                    WHEN orden = ? - 1 THEN orden + 1 
                                    ELSE orden END 
                                    WHERE id_criterio IN (?, (SELECT id_criterio FROM criterio WHERE orden = ? - 1 AND codigo_listacot = ?))");
                $stmt->execute([$id_criterio, $orden_actual, $id_criterio, $orden_actual, $codigo_listacot]);
                $_SESSION['success'] = "Criterio movido hacia arriba";
            }
        }
        elseif($accion == 'bajar') {
            // Reordenar criterios
            $stmt = $pdo->prepare("SELECT orden FROM criterio WHERE id_criterio = ?");
            $stmt->execute([$id_criterio]);
            $orden_actual = $stmt->fetch()['orden'];
            
            $stmt = $pdo->prepare("SELECT MAX(orden) as max_orden FROM criterio WHERE codigo_listacot = ?");
            $stmt->execute([$codigo_listacot]);
            $max_orden = $stmt->fetch()['max_orden'];
            
            if($orden_actual < $max_orden) {
                // Intercambiar con el criterio siguiente
                $stmt = $pdo->prepare("UPDATE criterio SET orden = CASE 
                                    WHEN id_criterio = ? THEN orden + 1 
                                    WHEN orden = ? + 1 THEN orden - 1 
                                    ELSE orden END 
                                    WHERE id_criterio IN (?, (SELECT id_criterio FROM criterio WHERE orden = ? + 1 AND codigo_listacot = ?))");
                $stmt->execute([$id_criterio, $orden_actual, $id_criterio, $orden_actual, $codigo_listacot]);
                $_SESSION['success'] = "Criterio movido hacia abajo";
            }
        }
        
        header("Location: gestion_criterios.php?sesion=" . $sesion_seleccionada);
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error al procesar la acción: " . $e->getMessage();
        header("Location: gestion_criterios.php?sesion=" . $sesion_seleccionada);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Criterios - Institución Nacional</title>
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
                <h1>Gestión de Criterios de Evaluación</h1>
                <p>Administra los criterios para la lista de cotejo</p>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="criteria-management">
                <!-- Información de la sesión -->
                <div class="session-info-card">
                    <h3>Información de la Sesión</h3>
                    <div class="session-details">
                        <p><strong>Curso:</strong> <?php echo $sesion_info['curso_nombre']; ?></p>
                        <p><strong>Sesión:</strong> <?php echo $sesion_info['titulo']; ?></p>
                        <p><strong>Lista de Cotejo:</strong> Código <?php echo $codigo_listacot; ?></p>
                    </div>
                    <div class="session-actions">
                        <a href="lista_cotejo.php?sesion=<?php echo $sesion_seleccionada; ?>" class="btn-primary">
                            <i class="fas fa-clipboard-list"></i> Ir a Lista de Cotejo
                        </a>
                        <a href="cursos.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Cursos
                        </a>
                    </div>
                </div>
                
                <!-- Agregar nuevo criterio -->
                <div class="add-criteria-card">
                    <h3><i class="fas fa-plus-circle"></i> Agregar Nuevo Criterio</h3>
                    <form method="POST" class="add-criteria-form">
                        <input type="hidden" name="agregar_criterio" value="1">
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción del Criterio:</label>
                            <textarea id="descripcion" name="descripcion" rows="2" required 
                                      placeholder="Describa el criterio de evaluación..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Agregar Criterio
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Criterios activos -->
                <div class="criteria-section active-criteria">
                    <div class="section-header">
                        <h3><i class="fas fa-check-circle"></i> Criterios Activos</h3>
                        <span class="badge"><?php echo count($criterios_activos); ?> criterios</span>
                    </div>
                    
                    <?php if(count($criterios_activos) > 0): ?>
                        <div class="criteria-list">
                            <?php foreach($criterios_activos as $criterio): ?>
                            <div class="criterion-item" data-id="<?php echo $criterio['id_criterio']; ?>">
                                <div class="criterion-content">
                                    <span class="criterion-number"><?php echo $criterio['orden']; ?></span>
                                    <span class="criterion-text"><?php echo $criterio['descripcion']; ?></span>
                                </div>
                                <div class="criterion-actions">
                                    <button class="btn-icon-small btn-edit" 
                                            onclick="openEditModal(<?php echo $criterio['id_criterio']; ?>, '<?php echo htmlspecialchars($criterio['descripcion']); ?>', <?php echo $criterio['orden']; ?>)"
                                            title="Editar criterio">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if($criterio['orden'] > 1): ?>
                                    <a href="?sesion=<?php echo $sesion_seleccionada; ?>&accion=subir&id=<?php echo $criterio['id_criterio']; ?>" 
                                       class="btn-icon-small" title="Mover arriba">
                                        <i class="fas fa-arrow-up"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if($criterio['orden'] < count($criterios_activos)): ?>
                                    <a href="?sesion=<?php echo $sesion_seleccionada; ?>&accion=bajar&id=<?php echo $criterio['id_criterio']; ?>" 
                                       class="btn-icon-small" title="Mover abajo">
                                        <i class="fas fa-arrow-down"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="?sesion=<?php echo $sesion_seleccionada; ?>&accion=desactivar&id=<?php echo $criterio['id_criterio']; ?>" 
                                       class="btn-icon-small btn-warning" title="Desactivar criterio">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                    <a href="?sesion=<?php echo $sesion_seleccionada; ?>&accion=eliminar&id=<?php echo $criterio['id_criterio']; ?>" 
                                       class="btn-icon-small btn-danger" 
                                       onclick="return confirm('¿Está seguro de que desea eliminar este criterio?')"
                                       title="Eliminar criterio">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <i class="fas fa-clipboard-list"></i>
                            <p>No hay criterios activos</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Criterios inactivos -->
                <?php if(count($criterios_inactivos) > 0): ?>
                <div class="criteria-section inactive-criteria">
                    <div class="section-header">
                        <h3><i class="fas fa-eye-slash"></i> Criterios Inactivos</h3>
                        <span class="badge"><?php echo count($criterios_inactivos); ?> criterios</span>
                    </div>
                    
                    <div class="criteria-list">
                        <?php foreach($criterios_inactivos as $criterio): ?>
                        <div class="criterion-item inactive" data-id="<?php echo $criterio['id_criterio']; ?>">
                            <div class="criterion-content">
                                <span class="criterion-number"><?php echo $criterio['orden']; ?></span>
                                <span class="criterion-text"><?php echo $criterio['descripcion']; ?></span>
                            </div>
                            <div class="criterion-actions">
                                <a href="?sesion=<?php echo $sesion_seleccionada; ?>&accion=activar&id=<?php echo $criterio['id_criterio']; ?>" 
                                   class="btn-icon-small btn-success" title="Activar criterio">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?sesion=<?php echo $sesion_seleccionada; ?>&accion=eliminar&id=<?php echo $criterio['id_criterio']; ?>" 
                                   class="btn-icon-small btn-danger" 
                                   onclick="return confirm('¿Está seguro de que desea eliminar este criterio?')"
                                   title="Eliminar criterio">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal para editar criterio -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Criterio</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" class="modal-form" id="editForm">
                <input type="hidden" name="editar_criterio" value="1">
                <input type="hidden" name="id_criterio" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_descripcion">Descripción del Criterio:</label>
                    <textarea id="edit_descripcion" name="descripcion" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_orden">Orden de visualización:</label>
                    <input type="number" id="edit_orden" name="orden" min="1" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn-secondary close-modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/script.js"></script>
    <script>
    // Funcionalidad del modal de edición
    function openEditModal(id, descripcion, orden) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_descripcion').value = descripcion;
        document.getElementById('edit_orden').value = orden;
        document.getElementById('editModal').style.display = 'block';
    }
    
    // Cerrar modales
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('editModal');
        const closeBtn = document.querySelector('#editModal .close');
        const closeModalBtn = document.querySelector('#editModal .close-modal');
        
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        closeModalBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>