import './bootstrap';

import Alpine from 'alpinejs';
import { booking, cookieNotice } from './booking';
import rideUpdates from './ride-updates';

window.Alpine = Alpine;

Alpine.data('booking', booking);
Alpine.data('cookieNotice', cookieNotice);
Alpine.data('rideUpdates', rideUpdates);
Alpine.start();
