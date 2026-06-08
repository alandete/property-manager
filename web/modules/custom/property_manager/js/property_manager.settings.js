(function (Drupal) {
  'use strict';

  Drupal.behaviors.propertyManagerSettingsPreview = {
    attach: function (context, settings) {
      var prefix = context.querySelector('[name="codigo_prefijo"]');
      var sep    = context.querySelector('[name="codigo_separador"]');
      var digits = context.querySelector('[name="codigo_digitos"]');
      var preview = context.querySelector('#pm-code-preview');

      if (!prefix || !sep || !digits || !preview) return;

      function update() {
        var p = (prefix.value || 'PROP').replace(/[^A-Z0-9]/g, '');
        var s = sep.value;
        var d = parseInt(digits.value, 10) || 5;
        var num = '1'.padStart(d, '0');
        preview.innerHTML = '<code style="font-size:1.2em;">' + p + s + num + '</code>';
      }

      prefix.addEventListener('input', function () {
        var pos = prefix.selectionStart;
        prefix.value = prefix.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        prefix.setSelectionRange(pos, pos);
        update();
      });
      sep.addEventListener('change', update);
      digits.addEventListener('change', update);
    }
  };

}(Drupal));
