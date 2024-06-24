/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { config, lang } from '../src/Initializer.js';
import ConfigViewModel from '../src/models/ConfigViewModel.js';
import Fixture from './helpers/fixture.js';
import Observer from '../src/Observer.js';
import State from '../src/State.js';
import Utils from '../src/Utils.js';
import ViewModel from '../src/ViewModel.js';

describe('ConfigViewModel tests', () => {

  let vm;
  let state;
  /** @type {HTMLSpanElement} */
  let intervalEl;
  /** @type {HTMLAnchorElement} */
  let setIntervalEl;
  /** @type {HTMLSelectElement} */
  let apiEl;
  /** @type {HTMLSelectElement} */
  let langEl;
  /** @type {HTMLSelectElement} */
  let unitsEl;
  const newInterval = 99;
  const newMapApi = 'openlayers';
  const newLang = 'pl';
  const newUnits = 'imperial';

  beforeEach((done) => {
    Fixture.load('main.html')
      .then(() => done())
      .catch((e) => done.fail(e));
  });

  beforeEach(() => {
    config.reinitialize();
    config.interval = 10;
    config.lang = 'en';
    config.units = 'metric';
    config.mapApi = 'gmaps';

    intervalEl = document.querySelector('#interval');
    apiEl = document.querySelector('#api');
    langEl = document.querySelector('#lang');
    unitsEl = document.querySelector('#units');
    setIntervalEl = document.querySelector('#set-interval');
    state = new State();
    vm = new ConfigViewModel(state);
    vm.init();
    spyOn(Utils, 'setCookie').and.returnValue(newInterval);
    spyOn(ConfigViewModel, 'reload');
    spyOn(lang, '_').and.returnValue('{placeholder}');
  });

  afterEach(() => {
    Fixture.clear();
    Observer.unobserveAll(lang);
  });

  it('should create instance with state as parameter', () => {
    // then
    expect(vm).toBeInstanceOf(ViewModel);
    expect(vm.state).toBe(state);
  });

  it('should get interval value from user prompt on interval click', (done) => {
    // given
    spyOn(window, 'prompt').and.returnValue(newInterval);
    // when
    setIntervalEl.click();
    // then
    setTimeout(() => {
      expect(intervalEl.innerHTML).toBe(newInterval.toString());
      expect(config.interval).toBe(newInterval);
      done();
    }, 100);
  });

  it('should update UI text and set cookie on config interval change', (done) => {
    // when
    config.interval = newInterval;
    // then
    setTimeout(() => {
      expect(intervalEl.innerHTML).toBe(newInterval.toString());
      expect(Utils.setCookie).toHaveBeenCalledWith('interval', newInterval);
      done();
    }, 100);
  });

  it('should update select value on config map API change', (done) => {
    // when
    config.mapApi = newMapApi;
    // then
    setTimeout(() => {
      expect(apiEl.value).toBe(newMapApi);
      expect(Utils.setCookie).toHaveBeenCalledWith('api', newMapApi);
      done();
    }, 100);
  });

  it('should update config map API on select value change', (done) => {
    // when
    apiEl.value = newMapApi;
    apiEl.dispatchEvent(new Event('change'));
    // then
    setTimeout(() => {
      expect(config.mapApi).toBe(newMapApi);
      expect(Utils.setCookie).toHaveBeenCalledWith('api', newMapApi);
      done();
    }, 100);
  });

  it('should update select value and do reload on config language change', (done) => {
    // when
    config.lang = newLang;
    // then
    setTimeout(() => {
      expect(langEl.value).toBe(newLang);
      expect(Utils.setCookie).toHaveBeenCalledWith('lang', newLang);
      expect(ConfigViewModel.reload).toHaveBeenCalledTimes(1);
      done();
    }, 100);
  });

  it('should update config language and do reload on select value change', (done) => {
    // when
    langEl.value = newLang;
    langEl.dispatchEvent(new Event('change'));
    // then
    setTimeout(() => {
      expect(config.lang).toBe(newLang);
      expect(Utils.setCookie).toHaveBeenCalledWith('lang', newLang);
      expect(ConfigViewModel.reload).toHaveBeenCalledTimes(1);
      done();
    }, 100);
  });

  it('should update select value and do reload on config units change', (done) => {
    // when
    config.units = newUnits;
    // then
    setTimeout(() => {
      expect(unitsEl.value).toBe(newUnits);
      expect(Utils.setCookie).toHaveBeenCalledWith('units', newUnits);
      expect(ConfigViewModel.reload).toHaveBeenCalledTimes(1);
      done();
    }, 100);
  });

  it('should update config units and do reload on select value change', (done) => {
    // when
    unitsEl.value = newUnits;
    unitsEl.dispatchEvent(new Event('change'));
    // then
    setTimeout(() => {
      expect(config.units).toBe(newUnits);
      expect(Utils.setCookie).toHaveBeenCalledWith('units', newUnits);
      expect(ConfigViewModel.reload).toHaveBeenCalledTimes(1);
      done();
    }, 100);
  });

});
