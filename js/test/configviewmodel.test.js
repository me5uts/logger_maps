/*
 * μlogger
 *
 * Copyright(C) 2019 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

import ConfigViewModel from '../src/configviewmodel.js';
import ViewModel from '../src/viewmodel.js';
import { config } from '../src/initializer.js';
import uState from '../src/state.js';
import uUtils from '../src/utils.js';

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

  beforeEach(() => {
    config.reinitialize();
    config.interval = 10;
    config.lang = 'en';
    config.units = 'metric';
    config.mapApi = 'gmaps';

    const fixture = `<div id="fixture">
                       <div class="section">
                         <a id="set-interval" data-bind="onSetInterval"><span id="interval" data-bind="interval">${config.interval}</span></a>
                      </div>
                      <div>
                        <label for="api">api</label>
                        <select id="api" name="api" data-bind="mapApi">
                          <option value="gmaps" selected>Google Maps</option>
                          <option value="openlayers">OpenLayers</option>
                        </select>
                      </div>
                      <div>
                        <label for="lang">lang</label>
                        <select id="lang" name="lang" data-bind="lang">
                            <option value="en" selected>English</option>
                            <option value="pl">Polish</option>
                        </select>
                      </div>
                      <div class="section">
                        <label for="units">units</label>
                        <select id="units" name="units" data-bind="units">
                          <option value="metric" selected>Metric</option>
                          <option value="imperial">Imperial</option>
                          <option value="nautical">Nautical</option>
                        </select>
                      </div>
                    </div>`;
    document.body.insertAdjacentHTML('afterbegin', fixture);
    intervalEl = document.querySelector('#interval');
    apiEl = document.querySelector('#api');
    langEl = document.querySelector('#lang');
    unitsEl = document.querySelector('#units');
    setIntervalEl = document.querySelector('#set-interval');
    state = new uState();
    vm = new ConfigViewModel(state);
    vm.init();
    spyOn(uUtils, 'setCookie').and.returnValue(newInterval);
    spyOn(ConfigViewModel, 'reload');
  });

  afterEach(() => {
    document.body.removeChild(document.querySelector('#fixture'));
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
      expect(uUtils.setCookie).toHaveBeenCalledWith('interval', newInterval);
      done();
    }, 100);
  });

  it('should update select value on config map API change', (done) => {
    // when
    config.mapApi = newMapApi;
    // then
    setTimeout(() => {
      expect(apiEl.value).toBe(newMapApi);
      expect(uUtils.setCookie).toHaveBeenCalledWith('api', newMapApi);
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
      expect(uUtils.setCookie).toHaveBeenCalledWith('api', newMapApi);
      done();
    }, 100);
  });

  it('should update select value and do reload on config language change', (done) => {
    // when
    config.lang = newLang;
    // then
    setTimeout(() => {
      expect(langEl.value).toBe(newLang);
      expect(uUtils.setCookie).toHaveBeenCalledWith('lang', newLang);
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
      expect(uUtils.setCookie).toHaveBeenCalledWith('lang', newLang);
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
      expect(uUtils.setCookie).toHaveBeenCalledWith('units', newUnits);
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
      expect(uUtils.setCookie).toHaveBeenCalledWith('units', newUnits);
      expect(ConfigViewModel.reload).toHaveBeenCalledTimes(1);
      done();
    }, 100);
  });

});
