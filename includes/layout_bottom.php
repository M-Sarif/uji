</div><!-- /.content -->
</div><!-- /.app-container -->

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