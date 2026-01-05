import { startStimulusApp } from '@symfony/stimulus-bundle';
import AiConsoleController from './controllers/ai_console_controller.js';
import BulkSelectController from './controllers/bulk_select_controller.js';
import IncomeDetailsController from './controllers/income_details_controller.js';
import ShipDetailsController from './controllers/ship_details_controller.js';
import SingleSelectTableController from './controllers/single_select_table_controller.js';
import YearLimitController from './controllers/year_limit_controller.js';

const app = startStimulusApp();

app.register('ai-console', AiConsoleController);
app.register('bulk-select', BulkSelectController);
app.register('income-details', IncomeDetailsController);
app.register('ship-details', ShipDetailsController);
app.register('single-select-table', SingleSelectTableController);
app.register('year-limit', YearLimitController);
