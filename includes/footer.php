<?php 
// File: includes/footer.php
// Menutup tag HTML, memuat skrip JS, dan menutup semua wrapper layout yang terbuka.
?>

    </div>
    </div>
</div>
<footer class="mt-5 py-3 bg-light border-top">
    <div class="container text-center">
        <p class="mb-0 text-muted">&copy; <?php echo date("Y"); ?> Flowerindo. Hak Cipta Dilindungi.</p>
    </div>
</footer>

<script src="../dist/js/bootstrap.bundle.min.js"></script> 

<script>
    document.getElementById("sidebarToggle").addEventListener("click", function() {
        var wrapper = document.getElementById("wrapper");
        // Toggle class 'toggled' untuk menyembunyikan/menampilkan sidebar
        wrapper.classList.toggle("toggled");
    });
    
    // Pastikan sidebar tersembunyi secara default pada perangkat mobile
    if (window.innerWidth < 992) {
        document.getElementById("wrapper").classList.add("toggled");
    }
</script>

</body>
</html>