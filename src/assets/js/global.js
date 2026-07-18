(function(){
    var d = document;

    // ---- Scroll-triggered entrance animations ----
    var observar = d.querySelectorAll('.panel, .tarjeta, .indicador-estado, .paneles-fila, .grupo-form');
    if (observar.length && 'IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: .1 });

        observar.forEach(function(el) {
            el.style.animationPlayState = 'paused';
            obs.observe(el);
        });
    }

    // ---- Table row staggering with CSS var --i ----
    var tablas = d.querySelectorAll('.tabla tbody');
    tablas.forEach(function(tbody) {
        var filas = tbody.querySelectorAll('tr');
        filas.forEach(function(tr, idx) {
            if (!tr.hasAttribute('style') || tr.style.getPropertyValue('--i') === '') {
                tr.style.setProperty('--i', idx + 1);
            }
        });
    });

    // ---- Modal close on overlay click ----
    d.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.style.display = 'none';
            }
        });
    });
})();
