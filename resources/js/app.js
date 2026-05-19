import './bootstrap';
import 'bootstrap';
import { Modal } from 'bootstrap';
import * as FilePond from 'filepond';
import 'filepond/dist/filepond.min.css';
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

import TomSelect from 'tom-select';

let changeModeBtn = document.querySelector('#changeModeBtn')
let html = document.querySelector('html');

if(localStorage.getItem("mode")){
        html.setAttribute("data-bs-theme", localStorage.getItem("mode"));
} else {
        localStorage.setItem("mode", "dark")
}

// changeModeBtn.addEventListener("click", ()=>{
//     if(localStorage.getItem("mode") == "light"){
//         html.setAttribute("data-bs-theme", "dark");
//         localStorage.setItem("mode", "dark")
//     } else {
//         html.setAttribute("data-bs-theme", "light");
//         localStorage.setItem("mode", "light")
//     }
// })


  (function() {
    const switchEl = document.getElementById('themeSwitch');
    const storageKey = 'bs-theme';
    const getPreferredTheme = () => {
      // se l’utente ha già salvato una preferenza, la prende
      const stored = localStorage.getItem(storageKey);
      if (stored) return stored;
      // altrimenti usa la preferenza del sistema
      return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
    };

    const applyTheme = (theme) => {
      document.documentElement.setAttribute('data-bs-theme', theme);
      // aggiorna lo stato dello switch
      switchEl.checked = (theme === 'dark');
    };

    // all’avvio: carica e applica
    const current = getPreferredTheme();
    applyTheme(current);

    // al cambio dello switch: salva e applica
    switchEl.addEventListener('change', () => {
      const newTheme = switchEl.checked ? 'dark' : 'light';
      localStorage.setItem(storageKey, newTheme);
      applyTheme(newTheme);
    });
  })();



// document.addEventListener('DOMContentLoaded', function () {
//     document.querySelectorAll('.tom-select').forEach((el) => {
//         new TomSelect(el, {
//             create: true,
//             persist: false,
//             selectOnTab: true,
//         });
//     });
//     document.querySelectorAll('.tom-select-multiple').forEach((el) => {
//         new TomSelect(el, {
//             selectOnTab: true,
//             maxItems: 99,
//         });
//     });

    
//   });
  function initTomSelect(container = document) {
    const selects = container.querySelectorAll('.tom-select, .tom-select-multiple');
    selects.forEach(el => {
      if (!el.tom_select) {
        new TomSelect(el, {
          create: el.classList.contains('tom-select'),
          persist: false,
          selectOnTab: true,
          maxItems: el.multiple ? 99 : 1,
        });
        el.tom_select = true;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {    
    initTomSelect(); // pagine “statiche”
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
    if(editModal){
      editModal.addEventListener('shown.bs.modal', () => {
        initTomSelect(editModal);
        initChunkedMediaUploads(editModal);
      });
    }

    Livewire.on('workUpdated', () => {
        const modal = Modal.getInstance(editModal);
        modal.hide();
    });
  });
