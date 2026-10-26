import './bootstrap';
import 'bootstrap';
import { Modal, Toast } from 'bootstrap';
import * as FilePond from 'filepond';
import 'filepond/dist/filepond.min.css';
import TomSelect from 'tom-select';
import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables-all.js';

const MAX_CHUNKED_UPLOAD_BYTES = 500 * 1024 * 1024;
const CHUNKED_UPLOAD_SIZE = 10 * 1024 * 1024;

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function livewireComponentFor(element) {
  const root = element.closest('[wire\\:id]');
  const componentId = root?.getAttribute('wire:id');

  return componentId && window.Livewire ? window.Livewire.find(componentId) : null;
}

function initChunkedMediaUploads(container = document) {
  container.querySelectorAll('.js-chunked-media-upload').forEach((input) => {
    if (input.dataset.filepondInitialized === '1') {
      return;
    }

    const component = livewireComponentFor(input);
    const context = input.dataset.mediaUploadContext;
    const modelId = input.dataset.mediaUploadModelId;
    const formToken = input.dataset.mediaUploadFormToken;

    if (!context || (!modelId && !formToken)) {
      return;
    }

    const params = new URLSearchParams({ context });

    if (modelId) {
      params.set('model_id', modelId);
    }

    if (formToken) {
      params.set('form_token', formToken);
    }

    input.dataset.filepondInitialized = '1';
    const uploadHeaders = {
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json',
    };

    FilePond.create(input, {
      allowMultiple: true,
      credits: false,
      chunkUploads: true,
      chunkForce: true,
      chunkSize: CHUNKED_UPLOAD_SIZE,
      maxParallelUploads: 2,
      labelIdle: 'Trascina qui gli allegati o <span class="filepond--label-action">selezionali</span>',
      labelFileProcessing: 'Caricamento',
      labelFileProcessingComplete: 'Caricamento completato',
      labelFileProcessingAborted: 'Caricamento annullato',
      labelFileProcessingError: 'Errore durante il caricamento',
      labelTapToCancel: 'tocca per annullare',
      labelTapToRetry: 'tocca per riprovare',
      beforeAddFile: (fileItem) => {
        if (fileItem.fileSize > MAX_CHUNKED_UPLOAD_BYTES) {
          window.alert('Ogni allegato puo pesare al massimo 500 MB.');

          return false;
        }

        return true;
      },
      server: {
        headers: uploadHeaders,
        process: {
          url: `/media/uploads/process?${params.toString()}`,
          method: 'POST',
        },
        patch: {
          url: '/media/uploads/process/',
          method: 'PATCH',
        },
        revert: {
          url: '/media/uploads/revert',
          method: 'DELETE',
        },
      },
      onprocessfile: (error, file) => {
        if (!error && file.serverId && component) {
          component.call('registerCompletedUploadSession', file.serverId);
        }
      },
      onremovefile: (error, file) => {
        if (!error && file.serverId && component) {
          component.call('unregisterCompletedUploadSession', file.serverId);
        }
      },
    });
  });
}

if (window.flatpickr && !window.flatpickr.l10ns.it) {
  window.flatpickr.l10ns.it = {
    weekdays: {
      shorthand: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
      longhand: ['Domenica', 'Lunedi', 'Martedi', 'Mercoledi', 'Giovedi', 'Venerdi', 'Sabato'],
    },
    months: {
      shorthand: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
      longhand: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
    },
    firstDayOfWeek: 1,
    ordinal: () => '',
    rangeSeparator: ' a ',
    weekAbbreviation: 'Sett',
    scrollTitle: 'Scorri per aumentare',
    toggleTitle: 'Clicca per cambiare',
    time_24hr: true,
  };
}

// Tema: il valore iniziale lo applica lo script inline nel <head> del layout, prima del primo paint
(function () {
  const switchEl = document.getElementById('themeSwitch');
  const storageKey = 'bs-theme';

  const readStoredTheme = () => {
    try {
      return localStorage.getItem(storageKey);
    } catch {
      return null;
    }
  };

  const preferredTheme = () => readStoredTheme()
    ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

  const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-bs-theme', theme);

    if (switchEl) {
      switchEl.checked = theme === 'dark';
    }
  };

  applyTheme(preferredTheme());

  switchEl?.addEventListener('change', () => {
    const newTheme = switchEl.checked ? 'dark' : 'light';

    try {
      localStorage.setItem(storageKey, newTheme);
    } catch {
      // Storage non disponibile: il tema vale solo per questa pagina
    }

    applyTheme(newTheme);
  });
})();

// Select delle pagine senza Livewire (es. gestione utenti). Le select dentro i componenti
// Livewire usano invece <x-tom-select>, che passa da Alpine.
function initTomSelect(container = document) {
  const selects = container.querySelectorAll('.tom-select, .tom-select-multiple');
  selects.forEach((el) => {
    if (!el.tom_select) {
      new TomSelect(el, {
        create: false,
        persist: false,
        selectOnTab: true,
        maxItems: el.multiple ? 99 : 1,
      });
      el.tom_select = true;
    }
  });
}

async function fetchLookup(url, { query = null, ids = [] } = {}) {
  const params = new URLSearchParams();

  if (query) {
    params.set('q', query);
  }

  ids.forEach((id) => params.append('ids[]', id));

  const response = await fetch(`${url}?${params.toString()}`, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  });

  if (!response.ok) {
    throw new Error(`Lookup failed with status ${response.status}`);
  }

  return response.json();
}

function toTomSelectValue(value, multiple) {
  if (multiple) {
    if (value === null || value === undefined || value === '') {
      return [];
    }

    return (Array.isArray(value) ? value : [value]).filter((item) => item !== null && item !== '').map(String);
  }

  return value === null || value === undefined ? '' : String(value);
}

document.addEventListener('alpine:init', () => {
  window.Alpine.data('tomSelectField', (config) => ({
    value: config.value,
    tomSelect: null,
    applying: false,

    init() {
      const settings = {
        create: false,
        // L'opzione vuota fa da placeholder (come prima); Backspace svuota la selezione
        allowEmptyOption: false,
        maxItems: config.multiple ? null : 1,
        maxOptions: null,
        plugins: config.multiple ? ['remove_button'] : [],
        render: {
          no_results: () => '<div class="no-results">Nessun risultato</div>',
        },
        onChange: (selected) => {
          if (this.applying) {
            return;
          }

          if (config.multiple) {
            this.value = (Array.isArray(selected) ? selected : [selected]).filter((item) => item !== '');
          } else {
            this.value = selected === '' ? null : selected;
          }
        },
      };

      if (config.remoteUrl) {
        Object.assign(settings, {
          valueField: 'value',
          labelField: 'text',
          searchField: ['text'],
          maxOptions: 50,
          loadThrottle: 300,
          shouldLoad: (query) => query.length >= 2,
          load: (query, callback) => {
            fetchLookup(config.remoteUrl, { query }).then(callback).catch(() => callback());
          },
        });
        settings.render.not_loading = (data) => (data.input.length < 2
          ? '<div class="no-results">Scrivi almeno 2 caratteri</div>'
          : null);
      }

      this.tomSelect = new TomSelect(this.$refs.select, settings);
      this.applyValue(this.value);
      this.$watch('value', (value) => this.applyValue(value));
    },

    async applyValue(value) {
      const tomSelect = this.tomSelect;

      if (!tomSelect) {
        return;
      }

      const target = toTomSelectValue(value, config.multiple);
      const wanted = config.multiple ? target : [target].filter((item) => item !== '');

      // Con le liste remote l'opzione del valore salvato potrebbe non essere ancora caricata
      if (config.remoteUrl) {
        const missing = wanted.filter((id) => !tomSelect.options[id]);

        if (missing.length > 0) {
          try {
            const options = await fetchLookup(config.remoteUrl, { ids: missing });
            options.forEach((option) => tomSelect.addOption(option));
          } catch {
            // Se la ricerca fallisce il valore resta comunque quello salvato lato server
          }
        }
      }

      this.applying = true;
      tomSelect.setValue(target, true);
      this.applying = false;
    },

    destroy() {
      this.tomSelect?.destroy();
    },
  }));
});

function showToast(message, type = 'success') {
  const container = document.getElementById('app-toasts');

  if (!container || !message) {
    return;
  }

  const variant = ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'success';
  const toastEl = document.createElement('div');
  toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
  toastEl.setAttribute('role', variant === 'danger' ? 'alert' : 'status');
  toastEl.setAttribute('aria-live', variant === 'danger' ? 'assertive' : 'polite');
  toastEl.setAttribute('aria-atomic', 'true');

  const wrapper = document.createElement('div');
  wrapper.className = 'd-flex';

  const body = document.createElement('div');
  body.className = 'toast-body';
  body.textContent = message;

  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'btn-close btn-close-white me-2 m-auto';
  close.setAttribute('data-bs-dismiss', 'toast');
  close.setAttribute('aria-label', 'Chiudi');

  wrapper.append(body, close);
  toastEl.append(wrapper);
  container.append(toastEl);

  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove(), { once: true });
  new Toast(toastEl, { delay: variant === 'danger' ? 8000 : 4000 }).show();
}

// I modali di dettaglio/modifica vengono riusati: finché Livewire non ha caricato il record
// richiesto si mostra uno spinner al posto dei dati del record aperto in precedenza
const MODAL_LOADING_TIMEOUT_MS = 15000;

document.addEventListener('show.bs.modal', (event) => {
  const modal = event.target;

  if (!modal.matches('[data-livewire-modal]') || !event.relatedTarget) {
    return;
  }

  modal.classList.add('is-loading');
  window.clearTimeout(modal.__loadingTimeout);
  modal.__loadingTimeout = window.setTimeout(() => modal.classList.remove('is-loading'), MODAL_LOADING_TIMEOUT_MS);
});

document.addEventListener('DOMContentLoaded', () => {
  initTomSelect(); // pagine "statiche"
  initChunkedMediaUploads();

  const mediaUploadObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) {
          initChunkedMediaUploads(node);
        }
      });
    });
  });

  mediaUploadObserver.observe(document.body, {
    childList: true,
    subtree: true,
  });

  const editModal = document.getElementById('editModal');
  if (editModal) {
    editModal.addEventListener('shown.bs.modal', () => {
      initChunkedMediaUploads(editModal);
    });
  }

  if (!window.Livewire) {
    return;
  }

  window.Livewire.on('workUpdated', () => {
    if (editModal) {
      Modal.getInstance(editModal)?.hide();
    }
  });

  window.Livewire.on('notify', (event) => {
    const payload = Array.isArray(event) ? event[0] : event;
    showToast(payload?.message, payload?.type);
  });

  // Alcuni componenti segnalano i rifiuti con dispatch('error', 'messaggio')
  window.Livewire.on('error', (event) => {
    const payload = Array.isArray(event) ? event[0] : event;
    const message = typeof payload === 'string' ? payload : payload?.message;
    showToast(message, 'danger');
  });

  window.Livewire.hook('commit', ({ component, succeed, fail }) => {
    const modal = component.el?.closest('.modal[data-livewire-modal]');

    if (!modal) {
      return;
    }

    const done = () => window.requestAnimationFrame(() => {
      modal.classList.remove('is-loading');
      window.clearTimeout(modal.__loadingTimeout);
    });

    succeed(done);
    fail(done);
  });
});
