<?php

namespace Drupal\property_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Property Manager — code generation settings.
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

    if ($needs_setup) {
      $form['welcome'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Bienvenida'),
        'message' => [
          '#markup' => '<p>' . $this->t('El módulo se instaló con los valores por defecto. Revisa los ajustes a continuación y guarda cuando estén listos. Puedes continuar con los valores por defecto sin problema.') . '</p>',
        ],
      ];
    }

    $form['codigo'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Código automático del inmueble'),
    ];

    $form['codigo']['codigo_prefijo'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Prefijo'),
      '#description'   => $this->t('Texto fijo al inicio del código. Ej: <code>PROP</code>, <code>INM</code>, <code>VNT</code>.'),
      '#default_value' => $config->get('codigo_prefijo') ?? 'PROP',
      '#size'          => 10,
      '#maxlength'     => 10,
      '#required'      => TRUE,
    ];

    $form['codigo']['codigo_separador'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Separador'),
      '#description'   => $this->t('Carácter entre el prefijo y el número. Puede quedar vacío. Ej: <code>-</code> → PROP-00001 · vacío → PROP00001 · <code>_</code> → PROP_00001.'),
      '#default_value' => $config->get('codigo_separador') ?? '-',
      '#size'          => 3,
      '#maxlength'     => 3,
    ];

    $form['codigo']['codigo_digitos'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Número de dígitos'),
      '#description'   => $this->t('Cantidad de dígitos del contador (se rellena con ceros). Ej: 5 → 00001.'),
      '#default_value' => $config->get('codigo_digitos') ?? 5,
      '#min'           => 1,
      '#max'           => 10,
      '#required'      => TRUE,
    ];

    $form['codigo']['preview'] = [
      '#type'   => 'fieldset',
      '#title'  => $this->t('Vista previa'),
      'example' => [
        '#markup' => '<p id="pm-code-preview">' . $this->_buildPreview($config->get('codigo_prefijo'), $config->get('codigo_separador'), $config->get('codigo_digitos')) . '</p>',
      ],
    ];

    $form['#attached']['library'][] = 'property_manager/settings';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('property_manager.settings')
      ->set('codigo_prefijo', $form_state->getValue('codigo_prefijo'))
      ->set('codigo_separador', $form_state->getValue('codigo_separador'))
      ->set('codigo_digitos', (int) $form_state->getValue('codigo_digitos'))
      ->save();

    // Clear the setup flag after first save.
    \Drupal::state()->delete('property_manager.needs_setup');

    parent::submitForm($form, $form_state);
  }

  /**
   * Builds a preview string of the generated code.
   */
  private function _buildPreview(?string $prefix, ?string $sep, ?int $digits): string {
    $prefix = $prefix ?? 'PROP';
    $sep = $sep ?? '-';
    $digits = $digits ?? 5;
    $example = $prefix . $sep . str_pad(1, $digits, '0', STR_PAD_LEFT);
    return '<code style="font-size:1.2em;">' . htmlspecialchars($example) . '</code>';
  }

}
