'use strict';

(function () {
  function initKdQuizAnswerSync() {
    var answerRows = Array.prototype.slice.call(document.querySelectorAll('.kdquiz-answer-row'));
    if (!answerRows.length) {
      return;
    }

    function syncAnswerState() {
      var selected = document.querySelector('.kdquiz-answer-radio:checked');
      answerRows.forEach(function (row) {
        var radio = row.querySelector('.kdquiz-answer-radio');
        var status = row.querySelector('.kdquiz-answer-status');
        var isSelected = radio === selected;

        row.classList.toggle('is-selected', Boolean(isSelected));

        if (!status) {
          return;
        }

        var activeText = status.getAttribute('data-active-text') || '';
        if (isSelected) {
          status.textContent = activeText;
          status.removeAttribute('aria-hidden');
          status.setAttribute('role', 'status');
        } else {
          status.textContent = '';
          status.setAttribute('aria-hidden', 'true');
          status.removeAttribute('role');
        }
      });
    }

    var radios = Array.prototype.slice.call(document.querySelectorAll('.kdquiz-answer-radio'));
    radios.forEach(function (radio) {
      radio.addEventListener('change', syncAnswerState);
    });

    syncAnswerState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKdQuizAnswerSync);
  } else {
    initKdQuizAnswerSync();
  }
})();
