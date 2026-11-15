import './bootstrap';

import Alpine from 'alpinejs';
import { booking, cookieNotice } from './booking';
import rideUpdates from './ride-updates';
import hero from './hero';
import driverLocation from './driver-location';

window.Alpine = Alpine;

Alpine.data('booking', booking);
Alpine.data('cookieNotice', cookieNotice);
Alpine.data('rideUpdates', rideUpdates);
Alpine.data('hero', hero);
Alpine.data('driverLocation', driverLocation);
Alpine.start();
