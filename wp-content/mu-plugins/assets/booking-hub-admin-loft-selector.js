(function () {
  'use strict';

  function isTargetAdminPage() {
    if (!document.body || !document.body.classList.contains('wp-admin')) {
      return false;
    }

    var params = new URLSearchParams(window.location.search || '');
    return params.get('page') === 'loft1325-new-booking';
  }

  function normalize(value) {
    return (value || '')
      .toString()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function findLoftInput() {
    var candidates = document.querySelectorAll('input[type="text"], input:not([type])');

    for (var i = 0; i < candidates.length; i++) {
      var input = candidates[i];
      var placeholder = normalize(input.getAttribute('placeholder'));
      var name = normalize(input.getAttribute('name'));
      var id = input.getAttribute('id');
      var labelText = '';

      if (id) {
        var label = document.querySelector('label[for="' + id.replace(/"/g, '\\"') + '"]');
        labelText = normalize(label ? label.textContent : '');
      }

      var matched =
        placeholder.indexOf('id loft') !== -1 ||
        labelText.indexOf('id loft') !== -1 ||
        name.indexOf('loft') !== -1;

      if (matched) {
        return input;
      }
    }

    return null;
  }

  function buildOptions(select, lofts) {
    var config = window.loft1325LoftSelector || {};
    var defaultLabel = config.defaultOption || 'Sélectionner un loft…';
    var emptyLabel = config.emptyOption || 'Aucun loft disponible';

    select.innerHTML = '';

    var defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = lofts.length ? defaultLabel : emptyLabel;
    select.appendChild(defaultOption);

    lofts.forEach(function (loft) {
      var option = document.createElement('option');
      option.value = String(loft.id);
      option.textContent = loft.label + ' (#' + loft.id + ')';
      select.appendChild(option);
    });
  }

  function upgradeField(input, lofts) {
    if (input.dataset.loftSelectorReady === '1') {
      return;
    }

    var select = document.createElement('select');
    select.className = input.className;
    select.id = (input.id || 'loft-id') + '-select';
    select.setAttribute('aria-label', input.getAttribute('aria-label') || 'ID loft');

    if (input.required) {
      select.required = true;
    }

    buildOptions(select, lofts);

    var currentValue = (input.value || '').trim();
    if (currentValue) {
      select.value = currentValue;
    }

    select.addEventListener('change', function () {
      input.value = select.value;
    });

    input.type = 'hidden';
    input.value = select.value;
    input.dataset.loftSelectorReady = '1';

    input.insertAdjacentElement('afterend', select);
  }

  function init() {
    if (!isTargetAdminPage()) {
      return;
    }

    var config = window.loft1325LoftSelector || {};
    if (!config.ajaxUrl || !config.action) {
      return;
    }

    var formData = new URLSearchParams();
    formData.append('action', config.action);

    fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: formData.toString()
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data.lofts)) {
          return;
        }

        function tryUpgrade() {
          var loftInput = findLoftInput();
          if (!loftInput) {
            return false;
          }

          upgradeField(loftInput, payload.data.lofts);
          return true;
        }

        if (tryUpgrade()) {
          return;
        }

        var observer = new MutationObserver(function () {
          if (tryUpgrade()) {
            observer.disconnect();
          }
        });

        observer.observe(document.body, {
          childList: true,
          subtree: true
        });

        window.setTimeout(function () {
          observer.disconnect();
        }, 10000);
      })
      .catch(function () {
        // Keep existing text input if options fail to load.
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
    return;
  }

  init();
})();
