// Interaksi umum: toggle sidebar pada layar kecil.
(function () {
    var body = document.body;
    var toggle = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (toggle) toggle.addEventListener('click', function () { body.classList.toggle('sidebar-open'); });
    if (backdrop) backdrop.addEventListener('click', function () { body.classList.remove('sidebar-open'); });
})();
