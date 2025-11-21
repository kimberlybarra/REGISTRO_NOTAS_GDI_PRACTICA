<header class="main-header">
    <div class="header-content">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logocole">
                <img src="img/logo.png" alt="Logo" class="logo">
                <span>IE Virgen de Fátima</span>
            </div>

        </div>
        
        <div class="header-right">
            <div class="user-menu">
                <div class="user-info">
                    <span class="user-name"><?php echo $_SESSION['docente_nombres'] . ' ' . $_SESSION['docente_apellidos']; ?></span>
                    <span class="user-role">Docente</span>
                </div>
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </div>
</header>