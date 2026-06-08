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

    $form['codigo']['codigo_digitos'] = [
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
