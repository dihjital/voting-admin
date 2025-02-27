import './bootstrap';
import { createSVGBar } from './chart-utils.js';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.createSVGBar = createSVGBar;
window.Alpine = Alpine;

Alpine.plugin(focus);
Alpine.start();