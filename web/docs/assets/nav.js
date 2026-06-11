(function () {
  var path = window.location.pathname.replace(/\\/g, '/');
  var inSubdir = /\/(analisis|fases|features|modulos)\/[^/]+$/.test(path);
  var base = inSubdir ? '../' : '';

  var items = [
    { label: 'General', type: 'section' },
    { href: 'index.html', label: 'Inicio' },

    { label: 'Tipos de contenido', type: 'section' },
    { href: 'analisis/tipo-contenido.html', label: 'Inmueble' },
    { href: 'analisis/agente.html',         label: 'Agente',              sub: true },
    { href: 'analisis/documento.html',      label: 'Documentos',          sub: true },

    { label: 'Análisis técnico', type: 'section' },
    { href: 'analisis/taxonomias.html',     label: 'Taxonomías' },
    { href: 'analisis/ubicacion.html',      label: 'Ubicación jerárquica', sub: true },
    { href: 'analisis/codigo-auto.html',    label: 'Campo Código',         sub: true },

    { label: 'Features', type: 'section' },
    { href: 'features/index.html',          label: 'Investigación' },
    { href: 'features/mapas.html',          label: 'Mapas y geolocalización', sub: true },
    { href: 'features/busqueda.html',       label: 'Búsqueda y filtros',       sub: true },
    { href: 'features/seo.html',            label: 'SEO avanzado',             sub: true },

    { label: 'Fases', type: 'section' },
    { href: 'fases/roadmap.html',           label: 'Roadmap general' },
    { href: 'fases/fase-1.html',            label: 'Fase 1 — MVP funcional',   sub: true },
    { href: 'fases/fase-2.html',            label: 'Fase 2 — Visual y UX',     sub: true },
    { href: 'fases/fase-3.html',            label: 'Fase 3 — Mapa',            sub: true },
    { href: 'fases/fase-4.html',            label: 'Fase 4 — Búsqueda',        sub: true },
    { href: 'fases/fase-5.html',            label: 'Fase 5 — Features extra',  sub: true },

    { label: 'Módulos', type: 'section' },
    { href: 'modulos/property_manager.html', label: 'property_manager' },
    { href: 'modulos/arquitectura.html',     label: 'Arquitectura y dependencias', sub: true },
    { href: 'modulos/estilos.html',          label: 'Estilos CSS',                 sub: true },
  ];

  var html = '<div class="sidebar-logo"><h1>Drupal Property Manager</h1><p>Documentación técnica</p></div><nav>';
  items.forEach(function (item) {
    if (item.type === 'section') {
      html += '<div class="nav-section">' + item.label + '</div>';
    } else {
      var cls = item.sub ? ' class="sub"' : '';
      html += '<a href="' + base + item.href + '"' + cls + '>' + item.label + '</a>';
    }
  });
  html += '</nav>';

  var sidebar = document.getElementById('sidebar');
  if (!sidebar) return;
  sidebar.innerHTML = html;

  // Mark active link by resolving href against current page
  var currentHref = window.location.href.split('?')[0].split('#')[0];
  sidebar.querySelectorAll('a[href]').forEach(function (link) {
    var a = document.createElement('a');
    a.href = link.getAttribute('href');
    var resolved = a.href.split('?')[0].split('#')[0];
    if (resolved === currentHref) {
      link.classList.add('active');
    }
  });
})();
