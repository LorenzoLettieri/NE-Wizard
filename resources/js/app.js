import './bootstrap';
import 'bootstrap';
import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables-all.js';

import TomSelect from 'tom-select';

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