import { startStimulusApp } from '@symfony/stimulus-bundle';
import ShipDetailsController from './controllers/ship_details_controller.js';

const app = startStimulusApp();
app.register('ship-details', ShipDetailsController);
