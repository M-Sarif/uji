(function () {
    'use strict';

    var form = document.getElementById('kdForm');
    if (!form) { return; }

    function $(id) { return document.getElementById(id); }
    function pad(n) { return (n < 10 ? '0' : '') + n; }

    var jenis = $('kdJenis'), jenisClear = $('kdJenisClear');
    var timeBlock = $('kdTimeBlock'), timeBtn = $('kdTime'), timeText = $('kdTimeText');
    var estH = $('kdEstH'), estM = $('kdEstM');
    var photoBtn = $('kdPhotoBtn'), photoDone = $('kdPhotoDone'), photoImg = $('kdPhotoImg'), photoDel = $('kdPhotoDel');
    var photoData = $('kdPhotoData');
    var note = $('kdNote'), noteClear = $('kdNoteClear');
    var send = $('kdSend');
    var modal = $('kdModal'), apply = $('kdApply'), cancel = $('kdCancel');

    var est = { set: false, h: 0, m: 0 };

    var routeBlock = $('kdRouteBlock'), routeBtn = $('kdRouteBtn'), routeVal = $('kdRouteVal');
    var routeInput = $('kdRouteValue');
    var routePage = $('kdRoutePage'), routeList = $('kdRouteList');
    var routeApply = $('kdRouteApply'), routeBack = $('kdRouteBack');
    var routeType = routeBlock ? routeBlock.getAttribute('data-type') : '';
    var routeBase = '';
    var routeApplied = null;
    var routeOpenOrder = '';

    function bisa() {
        var r = form.querySelector('input[name="bisa"]:checked');
        return r ? r.value : 'ya';
    }

    function refresh() {
        var hasJenis = jenis.value !== '';
        var hasNote  = note.value.trim() !== '';
        var hasPhoto = photoData.value !== '';
        var prediksi = bisa() === 'ya';
        var estOk    = prediksi && est.set;
        var ready    = hasJenis && hasPhoto && hasNote && (!prediksi || estOk);

        jenis.setAttribute('data-empty', hasJenis ? '0' : '1');
        jenisClear.hidden = !hasJenis;
        noteClear.hidden = note.value === '';
        timeBlock.hidden = !prediksi;
        var showRoute = !!routeBlock && jenis.value === routeType;
        if (routeBlock) { routeBlock.hidden = !showRoute; }
        if (routeInput) { routeInput.value = (showRoute && routeApplied) ? routeApplied.join(',') : ''; }

        form.setAttribute('data-jenis', hasJenis ? '1' : '0');
        form.setAttribute('data-bisa', prediksi ? 'ya' : 'tidak');
        form.setAttribute('data-est', estOk ? '1' : '0');
        form.setAttribute('data-photo', hasPhoto ? '1' : '0');
        form.setAttribute('data-note', hasNote ? '1' : '0');
        form.setAttribute('data-ready', ready ? '1' : '0');
        send.disabled = !ready;
        document.dispatchEvent(new CustomEvent('amt:state'));
    }

    jenis.addEventListener('change', refresh);
    jenisClear.addEventListener('click', function () { jenis.value = ''; refresh(); jenis.focus(); });

    form.querySelectorAll('input[name="bisa"]').forEach(function (r) { r.addEventListener('change', refresh); });

    var STEP_PX = 30;

    function Wheel(el, onChange) {
        var max = parseInt(el.getAttribute('data-max'), 10) || 59;
        var val = 0, lastY = 0, acc = 0, dragging = false, dragged = false;

        function wrap(n) { var size = max + 1; return ((n % size) + size) % size; }

        function render() {
            var html = '';
            [-1, 0, 1].forEach(function (off) {
                html += '<button type="button" tabindex="-1" class="kd-wheel__row' + (off === 0 ? ' is-sel' : '') + '"' +
                        (off !== 0 ? ' data-off="' + off + '"' : '') + '>' + pad(wrap(val + off)) + '</button>';
            });
            el.innerHTML = html;
            el.setAttribute('aria-valuemin', '0');
            el.setAttribute('aria-valuemax', String(max));
            el.setAttribute('aria-valuenow', String(val));
        }
        function set(n, silent) {
            val = wrap(n);
            render();
            if (!silent) { onChange(val); }
        }

        el.addEventListener('pointerdown', function (e) {
            dragging = true; dragged = false; lastY = e.clientY; acc = 0;
            try { el.setPointerCapture(e.pointerId); } catch (x) {}
        });
        el.addEventListener('pointermove', function (e) {
            if (!dragging) { return; }
            acc += e.clientY - lastY;
            lastY = e.clientY;
            if (Math.abs(acc) > 6) { dragged = true; }
            while (acc >= STEP_PX)  { set(val - 1); acc -= STEP_PX; }
            while (acc <= -STEP_PX) { set(val + 1); acc += STEP_PX; }
        });
        function end() { dragging = false; }
        el.addEventListener('pointerup', end);
        el.addEventListener('pointercancel', end);

        el.addEventListener('click', function (e) {
            if (dragged) { dragged = false; return; }
            var b = e.target.closest ? e.target.closest('[data-off]') : null;
            if (b) { set(val + parseInt(b.getAttribute('data-off'), 10)); }
        });
        el.addEventListener('wheel', function (e) {
            e.preventDefault();
            set(val + (e.deltaY > 0 ? 1 : -1));
        }, { passive: false });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp')   { e.preventDefault(); set(val - 1); }
            if (e.key === 'ArrowDown') { e.preventDefault(); set(val + 1); }
        });

        render();
        return { get: function () { return val; }, set: set };
    }

    var wheelH = Wheel($('kdWheelH'), updateApply);
    var wheelM = Wheel($('kdWheelM'), updateApply);

    function updateApply() { apply.disabled = (wheelH.get() === 0 && wheelM.get() === 0); }

    var host = document.querySelector('.app-container');
    if (host) { host.appendChild(modal); } else { modal.classList.add('is-fixed'); }

    function openModal() {
        wheelH.set(est.set ? est.h : 0, true);
        wheelM.set(est.set ? est.m : 0, true);
        updateApply();
        modal.hidden = false;
        modal.classList.add('is-open');
        document.dispatchEvent(new CustomEvent('amt:state'));
    }
    function closeModal() {
        modal.hidden = true;
        modal.classList.remove('is-open');
        timeBtn.focus();
        document.dispatchEvent(new CustomEvent('amt:state'));
    }

    timeBtn.addEventListener('click', openModal);
    cancel.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) { closeModal(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { closeModal(); } });
    apply.addEventListener('click', function () {
        if (apply.disabled) { return; }
        est = { set: true, h: wheelH.get(), m: wheelM.get() };
        estH.value = pad(est.h);
        estM.value = pad(est.m);
        timeText.textContent = pad(est.h) + ' Jam ' + pad(est.m) + ' Menit';
        timeText.classList.remove('is-ph');
        modal.hidden = true;
        modal.classList.remove('is-open');
        refresh();
    });

    var cam = null;
    if (window.AmtCamera) {
        cam = window.AmtCamera.create({
            facing: 'environment',
            switchable: true,
            title: 'Foto Bukti',
            onSave: function (dataUrl) { setPhoto(dataUrl); }
        });
    }
    function setPhoto(dataUrl) {
        photoData.value = dataUrl || '';
        photoImg.src = dataUrl || '';
        photoDone.hidden = !dataUrl;
        photoBtn.hidden = !!dataUrl;
        refresh();
    }
    photoBtn.addEventListener('click', function () { if (cam) { cam.open(); } });
    photoDel.addEventListener('click', function () { setPhoto(''); });

    note.addEventListener('input', refresh);
    noteClear.addEventListener('click', function () { note.value = ''; refresh(); note.focus(); });

    function Sortable(list, onChange) {
        var st = null;

        function items() { return [].slice.call(list.children); }
        function order() { return items().map(function (li) { return li.getAttribute('data-id'); }); }
        function renumber() {
            items().forEach(function (li, i) { li.querySelector('.kd-stop__no').textContent = String(i + 1); });
        }
        function snapshot() {
            var map = {};
            items().forEach(function (li) { map[li.getAttribute('data-id')] = li.offsetTop; });
            return map;
        }
        function animate(prev) {
            items().forEach(function (li) {
                if (st && li === st.el) { return; }
                var d = prev[li.getAttribute('data-id')] - li.offsetTop;
                if (!d) { return; }
                li.style.transition = 'none';
                li.style.transform = 'translateY(' + d + 'px)';
                li.getBoundingClientRect();
                li.style.transition = 'transform .18s ease';
                li.style.transform = '';
            });
        }
        function step(el, dir) {
            var all = items();
            var i = all.indexOf(el);
            var j = i + dir;
            if (j < 0 || j >= all.length) { return; }
            var prev = snapshot();
            if (dir < 0) { list.insertBefore(el, all[j]); } else { list.insertBefore(all[j], el); }
            animate(prev);
            renumber();
            onChange();
            el.focus();
        }

        list.addEventListener('pointerdown', function (e) {
            var el = e.target.closest ? e.target.closest('.kd-stop') : null;
            if (!el || st || (typeof e.button === 'number' && e.button > 0)) { return; }
            st = { el: el, startY: e.clientY, id: e.pointerId };
            el.classList.add('is-drag');
            el.style.transition = 'none';
            try { el.setPointerCapture(e.pointerId); } catch (x) {}
        });

        list.addEventListener('pointermove', function (e) {
            if (!st || e.pointerId !== st.id) { return; }
            var el = st.el;
            el.style.transform = 'translateY(' + (e.clientY - st.startY) + 'px)';
            var all = items();
            var others = all.filter(function (x) { return x !== el; });
            var center = el.offsetTop + (e.clientY - st.startY) + el.offsetHeight / 2;
            var idx = 0;
            others.forEach(function (x) {
                if (x.offsetTop + x.offsetHeight / 2 < center) { idx++; }
            });
            if (idx !== all.indexOf(el)) {
                var prev = snapshot();
                var oldTop = el.offsetTop;
                list.insertBefore(el, others[idx] || null);
                animate(prev);
                st.startY += el.offsetTop - oldTop;
                el.style.transform = 'translateY(' + (e.clientY - st.startY) + 'px)';
                renumber();
            }
        });

        function end(e) {
            if (!st || e.pointerId !== st.id) { return; }
            var el = st.el;
            st = null;
            el.classList.remove('is-drag');
            el.style.transition = 'transform .18s ease';
            el.style.transform = '';
            setTimeout(function () { el.style.transition = ''; }, 220);
            onChange();
        }
        list.addEventListener('pointerup', end);
        list.addEventListener('pointercancel', end);

        list.addEventListener('keydown', function (e) {
            var el = e.target.closest ? e.target.closest('.kd-stop') : null;
            if (!el) { return; }
            if (e.key === 'ArrowUp')   { e.preventDefault(); step(el, -1); }
            if (e.key === 'ArrowDown') { e.preventDefault(); step(el, 1); }
        });

        return { order: order, renumber: renumber };
    }

    var sorter = null;
    if (routePage && routeList) {
        routeBase = [].slice.call(routeList.children).map(function (li) { return li.getAttribute('data-id'); }).join(',');
        sorter = Sortable(routeList, function () {
            routeApply.disabled = (sorter.order().join(',') === routeOpenOrder);
        });
        var routeHost = document.querySelector('.app-container');
        if (routeHost) { routeHost.appendChild(routePage); } else { routePage.classList.add('is-fixed'); }
    }

    function arrangeRoute(ids) {
        var map = {};
        [].slice.call(routeList.children).forEach(function (li) { map[li.getAttribute('data-id')] = li; });
        ids.forEach(function (id) { if (map[id]) { routeList.appendChild(map[id]); } });
        sorter.renumber();
    }
    function routeText() {
        if (!routeApplied) { return 'Belum ditambahkan'; }
        return routeApplied.map(function (id) {
            var li = routeList.querySelector('[data-id="' + id + '"]');
            return li ? li.getAttribute('data-spbu') : id;
        }).join(' \u203A ');
    }
    function openRoute() {
        arrangeRoute((routeApplied || routeBase.split(',')));
        routeOpenOrder = sorter.order().join(',');
        routeApply.disabled = true;
        routePage.hidden = false;
        routePage.classList.add('is-open');
        document.dispatchEvent(new CustomEvent('amt:state'));
    }
    function closeRoute() {
        routePage.hidden = true;
        routePage.classList.remove('is-open');
        if (routeBtn) { routeBtn.focus(); }
        document.dispatchEvent(new CustomEvent('amt:state'));
    }

    if (routeBtn && routePage) {
        routeBtn.addEventListener('click', openRoute);
        routeBack.addEventListener('click', closeRoute);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !routePage.hidden) { closeRoute(); }
        });
        routeApply.addEventListener('click', function () {
            if (routeApply.disabled) { return; }
            var now = sorter.order();
            routeApplied = (now.join(',') === routeBase) ? null : now;
            routeVal.textContent = routeText();
            routeVal.classList.toggle('is-set', !!routeApplied);
            closeRoute();
            refresh();
        });
    }

    form.addEventListener('submit', function (e) {
        if (form.getAttribute('data-ready') !== '1') { e.preventDefault(); return; }
        setTimeout(function () { send.disabled = true; }, 0);
    });

    window.addEventListener('pageshow', function () {
        if (photoData.value && photoDone.hidden) { setPhoto(photoData.value); } else { refresh(); }
    });

    refresh();
})();