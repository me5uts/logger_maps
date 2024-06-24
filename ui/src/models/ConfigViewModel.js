/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { lang as $, config } from '../Initializer.js';
import ConfigDialogModel from './ConfigDialogModel.js';
import Utils from '../Utils.js';
import ViewModel from '../ViewModel.js';

/**
 * @class ConfigViewModel
 */
export default class ConfigViewModel extends ViewModel {
  /**
   * @param {State} state
   */
  constructor(state) {
    super(config);
    this.state = state;
    this.model.onSetInterval = () => this.setAutoReloadInterval();
    this.model.onConfigEdit = () => this.showConfigDialog();
  }

  /**
   * @return {ConfigViewModel}
   */
  init() {
    this.setObservers();
    this.bindAll();
    return this;
  }

  setObservers() {
    this.onChanged('mapApi', (api) => {
      Utils.setCookie('api', api);
    });
    this.onChanged('lang', (_lang) => {
      Utils.setCookie('lang', _lang);
      ConfigViewModel.reload();
    });
    this.onChanged('units', (units) => {
      Utils.setCookie('units', units);
      ConfigViewModel.reload();
    });
    this.onChanged('interval', (interval) => {
      Utils.setCookie('interval', interval);
    });
  }

  static reload() {
    window.location.reload();
  }

  setAutoReloadInterval() {
    const interval = parseInt(prompt($._('newinterval')));
    if (!isNaN(interval) && interval !== this.model.interval) {
      this.model.interval = interval;
    }
  }

  showConfigDialog() {
    const vm = new ConfigDialogModel(this);
    vm.init();
  }
}
