<?php

namespace Drupal\property_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Status page showing which Property Manager features are active.
 */
class PropertyManagerStatusController extends ControllerBase {

  /**
   * Renders the status page.
   */
  public function status(): array {
    $moduleHandler = \Drupal::moduleHandler();

    $features = [
      [
        'icon'   => '📋',
        'name'   => $this->t('Tipos de contenido y campos base'),
        'status' => 'ok',
        'detail' => $this->t('Inmueble, Agente y Documentos instalados.'),
      ],
      [
        'icon'   => '🔢',
        'name'   => $this->t('Código automático'),
        'status' => 'ok',
        'detail' => $this->t('Se genera al guardar cada inmueble.') . ' <a href="' . Url::fromRoute('property_manager.settings')->toString() . '">' . $this->t('Configurar') . '</a>',
      ],
      [
        'icon'   => '📄',
        'name'   => $this->t('Vistas de listado y administración'),
        'status' => 'ok',
        'detail' => $this->t('Listado público en /inmuebles · Panel de administración en /admin/inmuebles.'),
      ],
      [
        'icon'   => '🔗',
        'name'   => $this->t('URLs amigables (Pathauto)'),
        'status' => $moduleHandler->moduleExists('pathauto') ? 'ok' : 'off',
        'detail' => $moduleHandler->moduleExists('pathauto')
          ? $this->t('Patrón configurado: /inmuebles/[tipo]/[titulo].')
          : $this->t('Instala Pathauto: <code>composer require drupal/pathauto</code>'),
      ],
      [
        'icon'   => '🏷️',
        'name'   => $this->t('Metatags (Metatag)'),
        'status' => $moduleHandler->moduleExists('metatag') ? 'ok' : 'off',
        'detail' => $moduleHandler->moduleExists('metatag')
          ? $this->t('Título y descripción configurados para el tipo Inmueble.')
          : $this->t('Instala Metatag: <code>composer require drupal/metatag</code>'),
      ],
      [
        'icon'   => '🗺️',
        'name'   => $this->t('Sitemap XML (Simple Sitemap)'),
        'status' => $moduleHandler->moduleExists('simple_sitemap') ? 'ok' : 'off',
        'detail' => $moduleHandler->moduleExists('simple_sitemap')
          ? $this->t('Inmuebles incluidos en el sitemap.')
          : $this->t('Instala Simple Sitemap: <code>composer require drupal/simple_sitemap</code>'),
      ],
      [
        'icon'   => '🗺️',
        'name'   => $this->t('Mapa de propiedades (property_manager_geo)'),
        'status' => $moduleHandler->moduleExists('geofield') ? 'ok' : 'off',
        'detail' => $moduleHandler->moduleExists('geofield')
          ? $this->t('Mapa activo en la ficha de cada inmueble.')
          : $this->t('Instala: <code>composer require drupal/geofield drupal/leaflet drupal/geocoder</code>'),
      ],
      [
        'icon'   => '🔎',
        'name'   => $this->t('Búsqueda con filtros (property_manager_search)'),
        'status' => $moduleHandler->moduleExists('search_api') ? 'ok' : 'off',
        'detail' => $moduleHandler->moduleExists('search_api')
          ? $this->t('Búsqueda facetada activa.')
          : $this->t('Instala: <code>composer require drupal/search_api drupal/facets</code>'),
      ],
      [
        'icon'   => '📊',
        'name'   => $this->t('Schema.org / JSON-LD'),
        'status' => $moduleHandler->moduleExists('schemadotorg') ? 'ok' : 'off',
        'detail' => $moduleHandler->moduleExists('schemadotorg')
          ? $this->t('Datos estructurados RealEstateListing activos.')
          : $this->t('Instala: <code>composer require drupal/schemadotorg</code>'),
      ],
      [
        'icon'   => '📄',
        'name'   => $this->t('Ficha PDF (property_manager_pdf)'),
        'status' => $moduleHandler->moduleExists('entity_print') ? 'ok' : 'off',
        'detail' => $moduleHandler->moduleExists('entity_print')
          ? $this->t('Descarga de ficha PDF activa.')
          : $this->t('Instala: <code>composer require drupal/entity_print tecnickcom/tcpdf</code>'),
      ],
    ];

    $rows = [];
    foreach ($features as $feature) {
      $rows[] = [
        'icon'   => ['data' => ['#markup' => $feature['icon']]],
        'name'   => $feature['name'],
        'status' => [
          'data' => [
            '#type'       => 'markup',
            '#markup'     => '<span class="pm-status pm-status--' . $feature['status'] . '">' . ($feature['status'] === 'ok' ? $this->t('Activo') : $this->t('No instalado')) . '</span>',
          ],
        ],
        'detail' => ['data' => ['#markup' => $feature['detail']]],
      ];
    }

    $build['table'] = [
      '#type'   => 'table',
      '#header' => ['', $this->t('Feature'), $this->t('Estado'), $this->t('Detalle')],
      '#rows'   => $rows,
    ];

    $build['#attached']['library'][] = 'property_manager/status';

    return $build;
  }

}
