<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Inicio</span>
                </a>
            </li>
            <li>
                <a href="cursos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cursos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Cursos</span>
                </a>
            </li>
            <li>
                <a href="reportes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reporte de Notas</span>
                </a>
            </li>
            <li>
            <a href="reportes_completos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes_completos.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>Reporte Completo</span>
            </a>
        </li>


            <li>
                <a href="estudiantes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'estudiantes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Estudiantes</span>
                </a>
            </li>
            <li>
                <a href="configuracion.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configuraciones</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>