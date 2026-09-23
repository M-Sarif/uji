</div><!-- /.content -->
</div><!-- /.app-container -->

<script>
/* Toast notifikasi sukses (flash message): tampil sebentar lalu hilang sendiri. */
(function () {
    var toast = document.getElementById('toastSuccess');
    if (!toast) { return; }

    requestAnimationFrame(function () { toast.classList.add('is-visible'); });

    setTimeout(function () {
        toast.classList.remove('is-visible');
        toast.classList.add('is-leaving');
        toast.addEventListener('transitionend', function () { toast.remove(); }, { once: true });
    }, 2500);
})();
</script>

<script>
/* Petunjuk bisa disembunyikan; pilihan pengguna diingat selama sesi browser. */
(function () {
    var box = document.getElementById('tutorialBox');
    if (!box) { return; }

    var key = 'tutorialHidden:' + (box.dataset.screen || '');
    try {
        if (sessionStorage.getItem(key) === '1') { box.hidden = true; }
    } catch (e) {}

    document.getElementById('tutorialClose').addEventListener('click', function () {
        box.hidden = true;
        try { sessionStorage.setItem(key, '1'); } catch (e) {}
    });
})();
</script>

</body>
</html>