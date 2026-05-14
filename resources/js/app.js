import './bootstrap';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import persist from '@alpinejs/persist';
import nutritionCharts from './charts';

Alpine.plugin(focus);
Alpine.plugin(persist);
Alpine.data('nutritionCharts', nutritionCharts);

window.Alpine = Alpine;
Alpine.start();
