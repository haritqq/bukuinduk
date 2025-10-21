// Menjalankan fungsi setelah dokumen HTML selesai dimuat
document.addEventListener("DOMContentLoaded", function() {

    // --- Poin 2: Jam dan Tanggal di Header ---
    function updateTime() {
        const datetimeElement = document.getElementById('datetime');
        if (datetimeElement) {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            // Format ke Bahasa Indonesia
            datetimeElement.textContent = now.toLocaleDateString('id-ID', options);
        }
    }
    // Update jam setiap detik
    setInterval(updateTime, 1000);
    updateTime(); // Panggil sekali saat memuat


    // --- Poin 4: Navigasi Sidebar (Pindah Halaman) ---
    const sidebarLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    const pages = document.querySelectorAll('.page-content');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Hapus 'active' dari semua link dan halaman
            sidebarLinks.forEach(l => l.classList.remove('active'));
            pages.forEach(p => p.classList.remove('active'));

            // Tambah 'active' ke link yang diklik
            this.classList.add('active');
            
            // Tampilkan halaman yang sesuai
            const pageId = this.getAttribute('data-page');
            document.getElementById(pageId).classList.add('active');
        });
    });


    // --- Poin 5: Navigasi Panel Form (Tabs) ---
    const tabLinks = document.querySelectorAll('.form-nav .tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Hapus 'active' dari semua link tab dan konten tab
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Tambah 'active' ke link yang diklik
            this.classList.add('active');

            // Tampilkan konten tab yang sesuai
            const formId = this.getAttribute('data-form');
            document.getElementById(formId).classList.add('active');
        });
    });

    

});