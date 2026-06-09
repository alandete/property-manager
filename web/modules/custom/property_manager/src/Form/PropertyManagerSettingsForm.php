<?php

namespace Drupal\property_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Property Manager.
 */
class PropertyManagerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['property_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'property_manager_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('property_manager.settings');
    $needs_setup = \Drupal::state()->get('property_manager.needs_setup', FALSE);

    $form['#attached']['library'][] = 'property_manager/settings';

    if ($needs_setup) {
      $form['welcome'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Bienvenida'),
        'message' => [
          '#markup' => '<p>' . $this->t('El módulo se instaló con los valores por defecto. Revisa los ajustes a continuación y guarda cuando estén listos.') . '</p>',
        ],
      ];
    }

    // ── Vertical tabs ─────────────────────────────────────────────────────────
    $form['pm_tabs'] = [
      '#type'        => 'vertical_tabs',
      '#default_tab' => 'edit-tab-codigo',
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 1 — Código automático
    // ═══════════════════════════════════════════════════════════════════════════
    $form['tab_codigo'] = [
      '#type'  => 'details',
      '#title' => $this->t('Código automático'),
      '#group' => 'pm_tabs',
    ];

    // Tres campos en una fila de 3 columnas.
    $form['tab_codigo']['campos_row'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => ['pm-settings-3col']],
    ];

    $form['tab_codigo']['campos_row']['codigo_prefijo'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Prefijo'),
      '#description'   => $this->t('Texto fijo al inicio del código. Ej: <code>PROP</code>, <code>INM</code>, <code>VNT</code>.'),
      '#default_value' => $config->get('codigo_prefijo') ?? 'PROP',
      '#size'          => 10,
      '#maxlength'     => 10,
      '#required'      => TRUE,
    ];

    $form['tab_codigo']['campos_row']['codigo_separador'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Separador'),
      '#description'   => $this->t('Carácter entre el prefijo y el número.'),
      '#options'       => [
        ''  => $this->t('(ninguno) — PROP0001'),
        '-' => '- (guion) — PROP-0001',
        '_' => '_ (guion bajo) — PROP_0001',
        '.' => '. (punto) — PROP.0001',
      ],
      '#default_value' => $config->get('codigo_separador') ?? '-',
    ];

    $form['tab_codigo']['campos_row']['codigo_digitos'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Número de dígitos'),
      '#description'   => $this->t('Dígitos del contador, rellenos con ceros a la izquierda.'),
      '#options'       => [
        3 => '3 — 001',
        4 => '4 — 0001',
        5 => '5 — 00001',
        6 => '6 — 000001',
        7 => '7 — 0000001',
        8 => '8 — 00000001',
      ],
      '#default_value' => $config->get('codigo_digitos') ?? 5,
      '#required'      => TRUE,
    ];

    // Vista previa a todo el ancho.
    $form['tab_codigo']['preview'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => ['pm-settings-preview']],
      'label'   => ['#markup' => '<strong>' . $this->t('Vista previa del código') . '</strong>'],
      'example' => [
        '#markup' => '<p id="pm-code-preview">' . $this->buildPreview(
          $config->get('codigo_prefijo'),
          $config->get('codigo_separador'),
          $config->get('codigo_digitos')
        ) . '</p>',
      ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 2 — Secciones del formulario
    // ═══════════════════════════════════════════════════════════════════════════
    $secciones     = $config->get('form_secciones') ?: self::defaultSecciones();
    $campos_config = $config->get('form_campos')    ?: self::defaultCampos();
    $seccion_default = $config->get('form_seccion_default') ?? ($secciones[0]['id'] ?? 'basica');

    usort($secciones, fn($a, $b) => ($a['peso'] ?? 0) <=> ($b['peso'] ?? 0));

    $seccion_options = [];
    foreach ($secciones as $s) {
      $seccion_options[$s['id']] = $s['label'];
    }

    $form['tab_secciones'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Secciones del formulario'),
      '#description' => $this->t('Personaliza las pestañas del formulario de inmueble: nombres y orden de aparición.'),
      '#group'       => 'pm_tabs',
    ];

    // Tabla de secciones primero.
    $form['tab_secciones']['tabla_secciones'] = [
      '#type'      => 'table',
      '#caption'   => $this->t('Arrastra para reordenar. Edita el nombre directamente en la celda.'),
      '#header'    => [$this->t('Nombre de la pestaña'), $this->t('Orden')],
      '#tabledrag' => [
        [
          'action'       => 'order',
          'relationship' => 'sibling',
          'group'        => 'pm-seccion-peso',
        ],
      ],
      '#empty'     => $this->t('No hay secciones definidas.'),
    ];

    foreach ($secciones as $seccion) {
      $sid = $seccion['id'];
      $form['tab_secciones']['tabla_secciones'][$sid] = [
        '#attributes' => ['class' => ['draggable']],
        'label' => [
          '#type'          => 'textfield',
          '#title'         => $this->t('Nombre'),
          '#title_display' => 'invisible',
          '#default_value' => $seccion['label'],
          '#size'          => 40,
          '#required'      => TRUE,
          '#wrapper_attributes' => ['style' => 'min-width:200px'],
        ],
        'peso' => [
          '#type'          => 'weight',
          '#title'         => $this->t('Peso'),
          '#title_display' => 'invisible',
          '#default_value' => (int) ($seccion['peso'] ?? 0),
          '#delta'         => 20,
          '#attributes'    => ['class' => ['pm-seccion-peso']],
        ],
      ];
    }

    // Selector de pestaña activa después de la tabla.
    $form['tab_secciones']['form_seccion_default'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Pestaña activa al abrir el formulario'),
      '#options'       => $seccion_options,
      '#default_value' => $seccion_default,
      '#description'   => $this->t('Selecciona cuál de las secciones definidas arriba se muestra activa al cargar el formulario de inmueble.'),
      '#prefix'        => '<div class="pm-seccion-default-wrap">',
      '#suffix'        => '</div>',
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 3 — Ubicación de campos
    // ═══════════════════════════════════════════════════════════════════════════
    $campo_actual = [];
    foreach ($campos_config as $item) {
      $campo_actual[$item['campo']] = $item;
    }

    $form['tab_campos'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Ubicación de campos'),
      '#description' => $this->t('Asigna cada campo a una sección y define su orden dentro de ella.'),
      '#group'       => 'pm_tabs',
    ];

    $all_campos = $this->getAllCampos();

    $form['tab_campos']['tabla_campos'] = [
      '#type'   => 'table',
      '#header' => [$this->t('Campo'), $this->t('Sección'), $this->t('Orden')],
    ];

    foreach ($all_campos as $campo_id => $campo_label) {
      $info = $campo_actual[$campo_id] ?? ['seccion' => array_key_first($seccion_options), 'peso' => 0];
      $form['tab_campos']['tabla_campos'][$campo_id] = [
        'label' => [
          '#markup' => '<strong>' . $campo_label . '</strong>',
        ],
        'seccion' => [
          '#type'          => 'select',
          '#title'         => $this->t('Sección'),
          '#title_display' => 'invisible',
          '#options'       => $seccion_options,
          '#default_value' => $info['seccion'],
        ],
        'peso' => [
          '#type'          => 'number',
          '#title'         => $this->t('Orden'),
          '#title_display' => 'invisible',
          '#default_value' => (int) ($info['peso'] ?? 0),
          '#min'           => 0,
          '#max'           => 99,
          '#size'          => 3,
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $prefix = trim($form_state->getValue('codigo_prefijo'));
    if (!preg_match('/^[A-Z0-9]{1,10}$/', $prefix)) {
      $form_state->setErrorByName('codigo_prefijo', $this->t(
        'El prefijo solo puede contener letras mayúsculas y números (sin espacios ni caracteres especiales).'
      ));
    }
    else {
      $form_state->setValue('codigo_prefijo', $prefix);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // ── Código ──────────────────────────────────────────────────────────────
    $config = $this->config('property_manager.settings')
      ->set('codigo_prefijo',   $form_state->getValue('codigo_prefijo'))
      ->set('codigo_separador', $form_state->getValue('codigo_separador'))
      ->set('codigo_digitos',   (int) $form_state->getValue('codigo_digitos'));

    // ── Secciones ────────────────────────────────────────────────────────────
    $config->set('form_seccion_default', $form_state->getValue('form_seccion_default'));

    $tabla_secciones = $form_state->getValue('tabla_secciones') ?? [];
    $new_secciones = [];
    foreach ($tabla_secciones as $sid => $row) {
      $new_secciones[] = [
        'id'    => $sid,
        'label' => $row['label'],
        'peso'  => (int) $row['peso'],
      ];
    }
    usort($new_secciones, fn($a, $b) => $a['peso'] <=> $b['peso']);
    $config->set('form_secciones', $new_secciones);

    // ── Campos ────────────────────────────────────────────────────────────────
    $tabla_campos = $form_state->getValue('tabla_campos') ?? [];
    $new_campos = [];
    foreach ($tabla_campos as $campo_id => $row) {
      $new_campos[] = [
        'campo'   => $campo_id,
        'seccion' => $row['seccion'],
        'peso'    => (int) $row['peso'],
      ];
    }
    $config->set('form_campos', $new_campos);

    $config->save();

    \Drupal::state()->delete('property_manager.needs_setup');

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the complete list of manageable inmueble fields.
   */
  private function getAllCampos(): array {
    return [
      '_codigo'               => $this->t('Código (auto-generado)'),
      'field_tipo_oferta'     => $this->t('Tipo de oferta'),
      'field_tipo_inmueble'   => $this->t('Tipo de inmueble'),
      'field_estado_inmueble' => $this->t('Estado'),
      'field_precio'          => $this->t('Precio'),
      'field_pais'            => $this->t('País'),
      'field_departamento'    => $this->t('Departamento'),
      'field_ciudad'          => $this->t('Ciudad'),
      'field_direccion'       => $this->t('Dirección'),
      'field_barrio'          => $this->t('Barrio'),
      'field_zona'            => $this->t('Zona'),
      'field_habitaciones'    => $this->t('Habitaciones'),
      'field_banos'           => $this->t('Baños'),
      'field_parqueadero'     => $this->t('Parqueadero'),
      'field_area'            => $this->t('Área'),
      'field_estrato'         => $this->t('Estrato'),
      'field_caract_int'      => $this->t('Características internas'),
      'field_caract_ext'      => $this->t('Características externas'),
      'field_fotos'           => $this->t('Fotos'),
      'field_video'           => $this->t('Video'),
      'field_agente'          => $this->t('Agente'),
    ];
  }

  /**
   * Default section definitions. Also used by the module's hook_form_alter.
   */
  public static function defaultSecciones(): array {
    return [
      ['id' => 'basica',          'label' => 'Información básica',   'peso' => 0],
      ['id' => 'ubicacion',       'label' => 'Ubicación',            'peso' => 1],
      ['id' => 'caracteristicas', 'label' => 'Características',      'peso' => 2],
      ['id' => 'amenidades',      'label' => 'Amenidades',           'peso' => 3],
      ['id' => 'multimedia',      'label' => 'Multimedia',           'peso' => 4],
      ['id' => 'admin',           'label' => 'Información asociada', 'peso' => 5],
    ];
  }

  /**
   * Default field-to-section mapping. Also used by the module's hook_form_alter.
   */
  public static function defaultCampos(): array {
    return [
      ['campo' => '_codigo',               'seccion' => 'basica',          'peso' => 0],
      ['campo' => 'field_tipo_oferta',     'seccion' => 'basica',          'peso' => 1],
      ['campo' => 'field_tipo_inmueble',   'seccion' => 'basica',          'peso' => 2],
      ['campo' => 'field_estado_inmueble', 'seccion' => 'basica',          'peso' => 3],
      ['campo' => 'field_precio',          'seccion' => 'basica',          'peso' => 4],
      ['campo' => 'field_pais',            'seccion' => 'ubicacion',       'peso' => 0],
      ['campo' => 'field_departamento',    'seccion' => 'ubicacion',       'peso' => 1],
      ['campo' => 'field_ciudad',          'seccion' => 'ubicacion',       'peso' => 2],
      ['campo' => 'field_direccion',       'seccion' => 'ubicacion',       'peso' => 3],
      ['campo' => 'field_barrio',          'seccion' => 'ubicacion',       'peso' => 4],
      ['campo' => 'field_zona',            'seccion' => 'ubicacion',       'peso' => 5],
      ['campo' => 'field_habitaciones',    'seccion' => 'caracteristicas', 'peso' => 0],
      ['campo' => 'field_banos',           'seccion' => 'caracteristicas', 'peso' => 1],
      ['campo' => 'field_parqueadero',     'seccion' => 'caracteristicas', 'peso' => 2],
      ['campo' => 'field_area',            'seccion' => 'caracteristicas', 'peso' => 3],
      ['campo' => 'field_estrato',         'seccion' => 'caracteristicas', 'peso' => 4],
      ['campo' => 'field_caract_int',      'seccion' => 'amenidades',      'peso' => 0],
      ['campo' => 'field_caract_ext',      'seccion' => 'amenidades',      'peso' => 1],
      ['campo' => 'field_fotos',           'seccion' => 'multimedia',      'peso' => 0],
      ['campo' => 'field_video',           'seccion' => 'multimedia',      'peso' => 1],
      ['campo' => 'field_agente',          'seccion' => 'admin',           'peso' => 0],
    ];
  }

  /**
   * Builds a code preview string based on config.
   */
  private function buildPreview(?string $prefix, ?string $sep, ?int $digits): string {
    $prefix = $prefix ?? 'PROP';
    $sep    = $sep ?? '-';
    $digits = $digits ?? 5;
    $example = $prefix . $sep . str_pad(1, $digits, '0', STR_PAD_LEFT);
    return '<code style="font-size:1.2em;">' . htmlspecialchars($example) . '</code>';
  }

}
