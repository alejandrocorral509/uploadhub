import './bootstrap';
import '@fontsource-variable/figtree';
import '@fortawesome/fontawesome-free/css/all.css';

import * as Turbo from '@hotwired/turbo';
import 'alpine-turbo-drive-adapter';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

import { registerComponents } from './components';
import { registerPlayer } from './player';
import { episodeForm } from './uploader';

// Turbo hace que la navegación no recargue la página: así el reproductor no se corta
window.Turbo = Turbo;
window.Swal = Swal;
window.Alpine = Alpine;

registerComponents(Alpine);
registerPlayer(Alpine);
Alpine.data('episodeForm', episodeForm);

Alpine.start();

// Barra de progreso de Turbo con el color de la marca
Turbo.config.drive.progressBarDelay = 150;
