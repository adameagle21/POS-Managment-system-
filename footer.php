    </div> <!-- Close main-content -->
    
    <button id="menuToggle" style="position: fixed; bottom: 20px; right: 20px; background: #4f46e5; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; display: none; z-index: 1001; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <i class="fas fa-bars"></i>
    </button>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        function checkScreenSize() {
            if (window.innerWidth <= 992) {
                if(menuToggle) menuToggle.style.display = 'block';
                if(sidebar) sidebar.classList.remove('active');
            } else {
                if(menuToggle) menuToggle.style.display = 'none';
                if(sidebar) sidebar.classList.add('active');
            }
        }
        
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
        
        if(menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
    </script>
</body>
</html>