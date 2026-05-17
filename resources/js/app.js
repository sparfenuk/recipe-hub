import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import focus from '@alpinejs/focus';
import nutritionCharts from './charts';

Alpine.plugin(focus);
Alpine.data('nutritionCharts', nutritionCharts);

window.Alpine = Alpine;
Livewire.start();
