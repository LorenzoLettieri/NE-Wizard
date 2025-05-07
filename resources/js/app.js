import './bootstrap';
import 'bootstrap';

import TomSelect from 'tom-select';

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tom-select').forEach((el) => {
        new TomSelect(el, {
            create: true,
            persist: false,
            selectOnTab: true,
        });
    });
});