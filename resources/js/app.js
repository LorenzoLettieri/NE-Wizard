import './bootstrap';
import 'bootstrap';
import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables-all.js';

import TomSelect from 'tom-select';
import flatpickr from "flatpickr";

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

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tom-select').forEach((el) => {
        new TomSelect(el, {
            create: true,
            persist: false,
            selectOnTab: true,
        });
    });
    document.querySelectorAll('.tom-select-multiple').forEach((el) => {
        new TomSelect(el, {
            selectOnTab: true,
            maxItems: 99,
        });
    });

});