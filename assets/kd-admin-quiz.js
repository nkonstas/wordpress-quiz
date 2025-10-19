'use strict';

(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('input[type="checkbox"][name="kdquiz_enable_auto_insert"]');

    if (!toggle) {
      return;
    }

    var dependentFields = document.querySelectorAll('.kdquiz-auto-setting');
    var dependentDescriptions = document.querySelectorAll('.kdquiz-auto-description');

    var updateState = function () {
      var enabled = !!toggle.checked;

      Array.prototype.forEach.call(dependentFields, function (field) {
        field.disabled = !enabled;
        field.setAttribute('aria-disabled', enabled ? 'false' : 'true');
      });

      Array.prototype.forEach.call(dependentDescriptions, function (description) {
        var baseText = description.getAttribute('data-base') || '';
        var disabledNote = description.getAttribute('data-disabled-note') || '';
        var text = enabled || !disabledNote ? baseText : baseText + ' ' + disabledNote;
        description.textContent = text;
      });
    };

    toggle.addEventListener('change', updateState);
    updateState();
  });
})();
