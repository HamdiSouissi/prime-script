/**
 * Prime Script — JavaScript vanilla (aucune dépendance).
 *
 * Modules :
 *  - thème clair / sombre (mémorisé)
 *  - menu utilisateur
 *  - accordéons (speech, objections)
 *  - choix par cartes (secteur, étape, service, résultat)
 *  - volet Objections chargé en fetch
 *  - confirmations de suppression
 *  - recherche instantanée dans les listes
 *  - éditeur de texte enrichi simple + aperçu live
 *  - enregistrement de l'appel en fetch (sans rechargement)
 */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;

  /* ------------------------------------------------------------------ Utilitaires */
  function $(selector, scope) {
    return (scope || doc).querySelector(selector);
  }
  function $$(selector, scope) {
    return Array.prototype.slice.call((scope || doc).querySelectorAll(selector));
  }
  function csrfToken() {
    var meta = $('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }
  function baseUrl() {
    var meta = $('meta[name="base-url"]');
    return meta ? meta.getAttribute('content') : '';
  }
  function debounce(fn, delay) {
    var timer = null;
    return function () {
      var args = arguments;
      var self = this;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(self, args);
      }, delay || 200);
    };
  }

  /* ------------------------------------------------------------------ Thème */
  var THEME_KEY = 'prime-script-theme';

  function applyTheme(theme) {
    if (theme === 'light' || theme === 'dark') {
      root.setAttribute('data-theme', theme);
    } else {
      root.removeAttribute('data-theme');
    }
    $$('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', String(theme === 'dark'));
      var label = $('.theme-toggle__label', btn);
      if (label) {
        label.textContent = theme === 'dark' ? 'Mode clair' : 'Mode sombre';
      }
    });
  }

  function currentTheme() {
    var stored = null;
    try {
      stored = localStorage.getItem(THEME_KEY);
    } catch (e) {
      stored = null;
    }
    if (stored === 'light' || stored === 'dark') {
      return stored;
    }
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function initTheme() {
    applyTheme(currentTheme());
    $$('[data-theme-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var next = currentTheme() === 'dark' ? 'light' : 'dark';
        try {
          localStorage.setItem(THEME_KEY, next);
        } catch (e) {
          /* mode privé : on applique sans mémoriser */
        }
        applyTheme(next);
      });
    });
  }

  /* ------------------------------------------------------------------ Menu utilisateur */
  function initUserMenu() {
    var menu = $('[data-user-menu]');
    if (!menu) {
      return;
    }
    var button = $('[data-user-menu-button]', menu);
    var dropdown = $('[data-user-menu-dropdown]', menu);
    if (!button || !dropdown) {
      return;
    }

    function close() {
      dropdown.classList.remove('is-open');
      button.setAttribute('aria-expanded', 'false');
    }
    function toggle() {
      var open = dropdown.classList.toggle('is-open');
      button.setAttribute('aria-expanded', String(open));
    }

    button.addEventListener('click', function (event) {
      event.stopPropagation();
      toggle();
    });
    doc.addEventListener('click', function (event) {
      if (!menu.contains(event.target)) {
        close();
      }
    });
    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        close();
      }
    });
  }

  /* ------------------------------------------------------------------ Accordéons */
  function initAccordions() {
    // Accordéon générique
    $$('[data-accordion-button]').forEach(function (button) {
      var panel = doc.getElementById(button.getAttribute('aria-controls'));
      if (!panel) {
        return;
      }
      button.addEventListener('click', function () {
        var open = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!open));
        panel.classList.toggle('is-open', !open);
      });
    });

    // Blocs de speech : un seul ouvert à la fois sur mobile pour rester lisible,
    // tous consultables librement sur grand écran.
    var speechBlocks = $$('[data-speech-block]');
    speechBlocks.forEach(function (block, index) {
      var button = $('[data-speech-toggle]', block);
      var panel = $('.speech-block__panel', block);
      if (!button || !panel) {
        return;
      }
      var startOpen = block.hasAttribute('data-open') || index === 0;
      button.setAttribute('aria-expanded', String(startOpen));
      panel.classList.toggle('is-open', startOpen);

      button.addEventListener('click', function () {
        var isOpen = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!isOpen));
        panel.classList.toggle('is-open', !isOpen);
        if (!isOpen && window.innerWidth < 900) {
          // Referme les autres blocs sur petit écran.
          speechBlocks.forEach(function (other) {
            if (other === block) {
              return;
            }
            var ob = $('[data-speech-toggle]', other);
            var op = $('.speech-block__panel', other);
            if (ob && op) {
              ob.setAttribute('aria-expanded', 'false');
              op.classList.remove('is-open');
            }
          });
        }
      });
    });
  }

  /* ------------------------------------------------------------------ Cartes de choix */
  function initChoices() {
    $$('[data-choice-group]').forEach(function (group) {
      var inputs = $$('input[type="radio"]', group);
      var cards = $$('.choice, .result-option', group);

      function sync() {
        cards.forEach(function (card) {
          var input = $('input[type="radio"]', card);
          card.classList.toggle('is-selected', !!(input && input.checked));
        });
      }

      inputs.forEach(function (input) {
        input.addEventListener('change', sync);
      });
      sync();

      // Sélection au clavier (flèches) : comportement natif conservé, on resynchronise.
      group.addEventListener('keyup', function (event) {
        if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].indexOf(event.key) !== -1) {
          sync();
        }
      });
    });
  }

  /* ------------------------------------------------------------------ Phrases de conclusion */
  /* Affiche la phrase correspondant au résultat choisi sur l'écran de fin d'appel. */
  function initConclusionScripts() {
    var card = $('[data-conclusion-card]');
    if (!card) {
      return;
    }
    var scripts = $$('[data-conclusion]', card);
    var hint = $('[data-conclusion-hint]', card);
    var dateInput = $('[data-callback-date]');

    // Remplit « je vous rappelle {quand} » avec la date choisie juste au-dessus.
    function callbackLabel() {
      if (!dateInput || !dateInput.value) {
        return 'la semaine prochaine';
      }
      var parts = dateInput.value.split('-');
      if (parts.length !== 3) {
        return 'la semaine prochaine';
      }
      var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
      var days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
      return days[date.getDay()] + ' ' + date.getDate();
    }

    function sync(value) {
      var shown = 0;
      scripts.forEach(function (script) {
        var match = script.getAttribute('data-conclusion') === value;
        script.hidden = !match;
        if (match) {
          shown++;
          if (script.getAttribute('data-conclusion-key') === 'rappeler' && dateInput && dateInput.value) {
            // La phrase est écrite avec « la semaine prochaine » : dès qu'une date est
            // choisie au-dessus, on la remplace par le jour réel.
            script.innerHTML = script.innerHTML.replace('la semaine prochaine', callbackLabel());
          }
        }
      });
      if (hint) {
        hint.textContent = shown ? 'Phrase à dire' : "S'affiche selon le résultat choisi";
      }
    }

    var current = '';
    $$('input[name="result"]').forEach(function (input) {
      input.addEventListener('change', function () {
        current = input.value;
        sync(current);
      });
      if (input.checked) {
        current = input.value;
      }
    });
    if (dateInput) {
      dateInput.addEventListener('change', function () {
        sync(current);
      });
    }
    sync(current);
  }

  /* ------------------------------------------------------------------ Volet Objections */
  function initDrawer() {
    var drawer = $('[data-drawer]');
    if (!drawer) {
      return;
    }
    var panel = $('.drawer__panel', drawer);
    var lastFocus = null;

    function open() {
      lastFocus = doc.activeElement;
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      doc.body.style.overflow = 'hidden';
      var focusTarget = $('[data-drawer-close]', drawer) || panel;
      if (focusTarget) {
        focusTarget.focus();
      }
      loadObjections();
    }

    function close() {
      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      doc.body.style.overflow = '';
      if (lastFocus && lastFocus.focus) {
        lastFocus.focus();
      }
    }

    var loaded = false;
    function loadObjections() {
      var body = $('[data-drawer-body]', drawer);
      if (!body || loaded) {
        return;
      }
      var url = drawer.getAttribute('data-endpoint');
      if (!url) {
        return;
      }
      loaded = true;
      body.setAttribute('aria-busy', 'true');
      fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data && data.html) {
            body.innerHTML = data.html;
            initAccordions();
            initPreviewSearch(body);
          }
        })
        .catch(function () {
          loaded = false;
          body.innerHTML =
            '<div class="alert alert--error">Impossible de charger les objections. ' +
            'Fermez ce volet et réessayez.</div>';
        })
        .finally(function () {
          body.removeAttribute('aria-busy');
        });
    }

    $$('[data-drawer-open]').forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.preventDefault();
        open();
      });
    });
    $$('[data-drawer-close]').forEach(function (btn) {
      btn.addEventListener('click', close);
    });
    doc.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
        close();
      }
    });

    // Recherche d'objection dans le volet
    var search = $('[data-objection-search]', drawer);
    if (search) {
      search.addEventListener(
        'input',
        debounce(function () {
          var query = search.value.toLowerCase().trim();
          var items = $$('[data-objection]', drawer);
          var visible = 0;
          items.forEach(function (item) {
            var text = item.textContent.toLowerCase();
            var match = query === '' || text.indexOf(query) !== -1;
            item.style.display = match ? '' : 'none';
            if (match) {
              visible++;
            }
          });
          var empty = $('[data-objection-empty]', drawer);
          if (empty) {
            empty.hidden = visible !== 0;
          }
        }, 120)
      );
    }
  }

  /* ------------------------------------------------------------------ Aperçu (admin) */
  function initPreview() {
    var textarea = $('[data-preview-source]');
    if (!textarea) {
      return;
    }
    var output = $('[data-preview-output]');
    var note = $('[data-preview-note]');

    function unsafeToHtml(text) {
      // Aperçu côté client uniquement : le contenu est assaini côté serveur à l'enregistrement.
      return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\n/g, '<br>');
    }

    function render() {
      if (!output) {
        return;
      }
      var raw = textarea.value.trim();
      if (raw === '') {
        output.innerHTML = '<p class="text-muted">Le contenu apparaîtra ici.</p>';
        return;
      }

      var hasHtml = /<\/?(p|ul|ol|li|strong|b|em|i|br|h4)\b/i.test(raw);
      if (!hasHtml) {
        // Conversion « texte saisi au kilomètre » → paragraphes et puces.
        var lines = raw.split(/\n+/);
        var html = '';
        var inList = false;
        lines.forEach(function (line) {
          var trimmed = line.trim();
          if (trimmed === '') {
            return;
          }
          if (/^[-*•]\s+/.test(trimmed)) {
            if (!inList) {
              html += '<ul>';
              inList = true;
            }
            html += '<li>' + unsafeToHtml(trimmed.replace(/^[-*•]\s+/, '')) + '</li>';
          } else {
            if (inList) {
              html += '</ul>';
              inList = false;
            }
            html += '<p>' + unsafeToHtml(trimmed) + '</p>';
          }
        });
        if (inList) {
          html += '</ul>';
        }
        output.innerHTML = html;
      } else {
        // Déjà du HTML : on retire tout ce qui n'est pas autorisé pour l'aperçu.
        var allowed = /^(P|UL|OL|LI|STRONG|B|EM|I|BR|H4)$/;
        var temp = doc.createElement('div');
        temp.innerHTML = raw;
        var walk = function (node) {
          Array.prototype.slice.call(node.children).forEach(function (child) {
            if (!allowed.test(child.tagName)) {
              var text = doc.createTextNode(child.textContent);
              child.replaceWith(text);
              return;
            }
            Array.prototype.slice.call(child.attributes).forEach(function (attr) {
              child.removeAttribute(attr.name);
            });
            walk(child);
          });
        };
        walk(temp);
        output.innerHTML = temp.innerHTML;
      }

      // Remplacement des variables par des exemples lisibles.
      var samples = { entreprise_client: 'Atelier Lumen', contact: 'Mme Duval', secteur: 'Architectes', prenom_commercial: 'Hamdi', service: 'Site web' };
      var html = output.innerHTML.replace(/\{([a-z_]+)\}/gi, function (match, name) {
        return samples[name] ? '<em>' + samples[name] + '</em>' : '';
      });
      output.innerHTML = html;

      if (note) {
        note.hidden = false;
      }
    }

    textarea.addEventListener('input', debounce(render, 180));
    render();
  }

  /* ------------------------------------------------------------------ Éditeur simple */
  function initEditor() {
    $$('[data-editor]').forEach(function (editor) {
      var textarea = $('textarea', editor);
      if (!textarea) {
        return;
      }
      $$('[data-editor-action]', editor).forEach(function (button) {
        button.addEventListener('click', function () {
          var action = button.getAttribute('data-editor-action');
          wrapSelection(textarea, action);
          textarea.dispatchEvent(new Event('input', { bubbles: true }));
          textarea.focus();
        });
      });
      $$('[data-insert-variable]').forEach(function (chip) {
        chip.addEventListener('click', function () {
          insertAtCursor(textarea, chip.getAttribute('data-insert-variable'));
          textarea.dispatchEvent(new Event('input', { bubbles: true }));
          textarea.focus();
        });
      });
    });
  }

  function wrapSelection(textarea, action) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var value = textarea.value;
    var selected = value.slice(start, end);
    var before = '';
    var after = '';

    switch (action) {
      case 'bold':
        before = '<strong>';
        after = '</strong>';
        break;
      case 'italic':
        before = '<em>';
        after = '</em>';
        break;
      case 'list':
        before = '<ul>\n  <li>';
        after = '</li>\n  <li></li>\n</ul>';
        break;
      case 'paragraph':
        before = '<p>';
        after = '</p>';
        break;
      case 'variable':
        before = '{';
        after = '}';
        break;
      default:
        return;
    }

    textarea.value = value.slice(0, start) + before + selected + after + value.slice(end);
    var cursor = start + before.length + selected.length;
    textarea.setSelectionRange(cursor, cursor);
  }

  function insertAtCursor(textarea, text) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var value = textarea.value;
    textarea.value = value.slice(0, start) + text + value.slice(end);
    var cursor = start + text.length;
    textarea.setSelectionRange(cursor, cursor);
  }

  /* ------------------------------------------------------------------ Confirmations */
  function initConfirmations() {
    doc.addEventListener('submit', function (event) {
      var form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }
      var message = form.getAttribute('data-confirm');
      if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
      }
      // Anti double-clic sur les actions POST.
      var submitter = event.submitter;
      if (submitter instanceof HTMLElement && form.method.toLowerCase() === 'post') {
        var text = submitter.getAttribute('data-loading-text');
        if (text) {
          submitter.textContent = text;
        }
        submitter.setAttribute('aria-disabled', 'true');
        setTimeout(function () {
          submitter.removeAttribute('aria-disabled');
        }, 4000);
      }
    });
  }

  /* ------------------------------------------------------------------ Recherche locale */
  function initPreviewSearch(scope) {
    $$('[data-local-search]', scope).forEach(function (input) {
      var target = doc.querySelector(input.getAttribute('data-local-search'));
      if (!target) {
        return;
      }
      input.addEventListener(
        'input',
        debounce(function () {
          var query = input.value.toLowerCase().trim();
          var rows = $$('tbody tr', target);
          var visible = 0;
          rows.forEach(function (row) {
            if (row.hasAttribute('data-static')) {
              return;
            }
            var match = query === '' || row.textContent.toLowerCase().indexOf(query) !== -1;
            row.hidden = !match;
            if (match) {
              visible++;
            }
          });
          var empty = $('[data-search-empty]');
          if (empty) {
            empty.hidden = visible !== 0;
          }
        }, 140)
      );
    });
  }

  /* ------------------------------------------------------------------ Fin d'appel (fetch) */
  function initCallEnd() {
    var form = $('[data-call-end-form]');
    if (!form) {
      return;
    }
    var resultInputs = $$('input[name="result"]', form);
    var callbackField = $('[data-callback-field]', form);
    var submit = $('[data-call-end-submit]', form);

    function syncCallback() {
      var value = '';
      resultInputs.forEach(function (input) {
        if (input.checked) {
          value = input.value;
        }
      });
      if (callbackField) {
        callbackField.hidden = value !== 'rappeler' && value !== 'devis';
      }
    }
    resultInputs.forEach(function (input) {
      input.addEventListener('change', syncCallback);
    });
    syncCallback();

    form.addEventListener('submit', function (event) {
      if (!window.fetch || form.hasAttribute('data-no-ajax')) {
        return;
      }
      event.preventDefault();
      if (submit) {
        submit.setAttribute('aria-disabled', 'true');
        submit.classList.add('is-loading');
      }
      var data = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            return { ok: response.ok, status: response.status, payload: payload };
          });
        })
        .then(function (result) {
          if (result.ok && result.payload.ok) {
            window.location.href = result.payload.redirect;
            return;
          }
          throw new Error((result.payload && result.payload.message) || 'Enregistrement impossible.');
        })
        .catch(function (error) {
          if (submit) {
            submit.removeAttribute('aria-disabled');
            submit.classList.remove('is-loading');
          }
          showFormError(form, error.message);
        });
    });
  }

  function showFormError(form, message) {
    var box = $('[data-form-error]', form.parentNode) || $('[data-form-error]');
    if (box) {
      box.textContent = message;
      box.hidden = false;
      box.focus();
      return;
    }
    window.alert(message);
  }

  /* ------------------------------------------------------------------ Changement de service */
  function initServiceSwitch() {
    var switcher = $('[data-service-switcher]');
    if (!switcher) {
      return;
    }
    switcher.scrollIntoView({ block: 'nearest', inline: 'center' });
  }

  /* ------------------------------------------------------------------ Copie dans le presse-papiers */
  function initCopy() {
    $$('[data-copy-target]').forEach(function (button) {
      button.addEventListener('click', function () {
        var target = doc.querySelector(button.getAttribute('data-copy-target'));
        if (!target) {
          return;
        }
        var text = target.innerText;
        var done = function () {
          var original = button.getAttribute('data-original-label') || button.textContent;
          button.setAttribute('data-original-label', original);
          button.textContent = 'Copié !';
          setTimeout(function () {
            button.textContent = original;
          }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(done, function () {
            window.alert('Copie impossible sur ce navigateur.');
          });
        } else {
          var range = doc.createRange();
          range.selectNodeContents(target);
          var selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(range);
          try {
            doc.execCommand('copy');
            done();
          } catch (e) {
            window.alert('Copie impossible sur ce navigateur.');
          }
          selection.removeAllRanges();
        }
      });
    });
  }

  /* ------------------------------------------------------------------ Démarrage */
  function boot() {
    initTheme();
    initUserMenu();
    initAccordions();
    initChoices();
    initConclusionScripts();
    initDrawer();
    initPreview();
    initEditor();
    initConfirmations();
    initPreviewSearch(doc);
    initCallEnd();
    initServiceSwitch();
    initCopy();
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
