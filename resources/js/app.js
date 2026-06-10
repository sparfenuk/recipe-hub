import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import focus from '@alpinejs/focus';
import nutritionCharts from './charts';

Alpine.plugin(focus);
Alpine.data('nutritionCharts', nutritionCharts);

/**
 * Keyboard + screen-reader support shared by the catalog's multi-select
 * dropdowns (category / cuisine) and the ingredient autocomplete (UX.13).
 *
 * Markup contract:
 *   - trigger/input: @keydown="onKeydown($event)" :aria-expanded="open"
 *   - panel:        x-ref="list" role="listbox"
 *   - each option:  role="option" (a real <button> so .click() reuses wire:click)
 *
 * Selection is delegated to the existing option buttons via .click(), so the
 * server-side Livewire toggle stays the single source of truth and the mouse
 * path is untouched.
 */
function listNav() {
    return {
        open: false,
        activeIndex: -1,
        openPanel() {
            this.open = true;
            this.activeIndex = -1;
        },
        close() {
            this.open = false;
            this.activeIndex = -1;
        },
        toggle() {
            this.open ? this.close() : this.openPanel();
        },
        options() {
            return this.$refs.list
                ? Array.from(this.$refs.list.querySelectorAll('[role="option"]'))
                : [];
        },
        move(dir) {
            const opts = this.options();
            if (!opts.length) {
                return;
            }
            this.activeIndex = (this.activeIndex + dir + opts.length) % opts.length;
            const el = opts[this.activeIndex];
            if (el) {
                el.scrollIntoView({ block: 'nearest' });
            }
        },
        clickActive() {
            const opts = this.options();
            if (this.activeIndex >= 0 && opts[this.activeIndex]) {
                opts[this.activeIndex].click();
                return true;
            }
            return false;
        },
        onKeydown(e) {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (!this.open) {
                        this.openPanel();
                    }
                    this.move(1);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if (!this.open) {
                        this.openPanel();
                    }
                    this.move(-1);
                    break;
                case 'Enter':
                    if (this.open && this.activeIndex >= 0) {
                        e.preventDefault();
                        this.clickActive();
                    }
                    break;
                case 'Escape':
                    if (this.open) {
                        e.preventDefault();
                        this.close();
                    }
                    break;
            }
        },
    };
}

Alpine.data('listNav', listNav);

window.Alpine = Alpine;
Livewire.start();
