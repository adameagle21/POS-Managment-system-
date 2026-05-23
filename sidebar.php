<div class="sidebar">
    <div class="sidebar-header">
        <h3>🚗 Adam Car</h3>
        <p>Accessories System</p>
    </div>
    
    <div class="sidebar-menu">
        <!-- Dashboard -->
        <a href="index.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- POS System -->
        <a href="pos.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>POS System</span>
        </a>
        
        <!-- Products Management -->
        <a href="products.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
            <i class="fas fa-boxes"></i>
            <span>Products</span>
        </a>
        
        <!-- Sales Management -->
        <a href="sales.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            <span>Sales</span>
        </a>
        
        <!-- ========== NEW MENU ITEMS ========== -->
        
        
        
        <!-- Categories (NEW) -->
        <a href="categories.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>
        
        <!-- Expenses (NEW) -->
        <a href="expenses.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : '' ?>">
            <i class="fas fa-receipt"></i>
            <span>Expenses</span>
        </a>
        
        <!-- ========== END NEW MENU ITEMS ========== -->
        
        <!-- User Management -->
        <a href="users.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        
        <!-- Reports -->
        <a href="reports.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i>
            <span>Reports</span>
        </a>
        
        <div class="menu-divider"></div>
        
        <!-- Profile -->
        <a href="profile.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        
        <!-- Logout -->
        <a href="logout.php" class="menu-item" style="color: #ff6b6b;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
/* Sidebar Styles */
.sidebar {
    width: 280px;
    background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%);
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    padding: 20px 0;
    z-index: 1000;
    transition: all 0.3s ease;
    box-shadow: 5px 0 25px rgba(0,0,0,0.15);
    overflow-y: auto;
}

/* Scrollbar */
.sidebar::-webkit-scrollbar { width: 5px; }
.sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.sidebar::-webkit-scrollbar-thumb { background: #4f46e5; border-radius: 10px; }

/* Sidebar Header */
.sidebar-header {
    text-align: center;
    padding: 0 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    margin-bottom: 20px;
}
.sidebar-header h3 {
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.sidebar-header p {
    color: #94a3b8;
    font-size: 0.7rem;
    margin-top: 5px;
}

/* Sidebar Menu */
.sidebar-menu {
    padding: 0 15px;
}
.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    margin: 4px 0;
    color: #cbd5e1;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s;
    font-weight: 500;
    font-size: 0.85rem;
}
.menu-item:hover {
    background: rgba(255,255,255,0.08);
    color: white;
    transform: translateX(5px);
}
.menu-item i {
    width: 22px;
    font-size: 0.95rem;
    text-align: center;
}
.menu-item.active {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}
.menu-divider {
    height: 1px;
    background: rgba(255,255,255,0.08);
    margin: 12px 0;
}

/* Main Content */
.main-content {
    margin-left: 280px;
    padding: 20px;
    min-height: 100vh;
}

/* Top Bar */
.top-bar {
    background: white;
    border-radius: 16px;
    padding: 12px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.page-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.user-badge {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.7rem;
}
.user-avatar {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

/* Mobile Toggle */
.menu-toggle-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #4f46e5;
    color: white;
    border: none;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: none;
    z-index: 1001;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(79,70,229,0.4);
}

/* Responsive */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.active {
        transform: translateX(0);
    }
    .main-content {
        margin-left: 0;
    }
    .menu-toggle-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>

<!-- Mobile Toggle Button -->
<button id="menuToggle" class="menu-toggle-btn">
    <i class="fas fa-bars"></i>
</button>

<script>
// Mobile menu toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.querySelector('.sidebar');

if(menuToggle) {
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    if(window.innerWidth <= 992) {
        if(sidebar && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});
</script>