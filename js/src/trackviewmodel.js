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

import { lang as $, auth, config } from './initializer.js';
import TrackDialogModel from './trackdialogmodel.js';
import ViewModel from './viewmodel.js';
import uAlert from './alert.js';
import uObserve from './observe.js';
import uPositionSet from './positionset.js';
import uSelect from './select.js';
import uTrack from './track.js';
import uUtils from './utils.js';

/**
 * @class TrackViewModel
 */
export default class TrackViewModel extends ViewModel {

  /**
   * @param {uState} state
   */
  constructor(state) {
    super({
      /** @type {uTrack[]} */
      trackList: [],
      /** @type {string} */
      currentTrackId: '',
      /** @type {boolean} */
      showLatest: false,
      /** @type {boolean} */
      autoReload: false,
      /** @type {string} */
      inputFile: false,
      /** @type {string} */
      summary: false,
      /** @type {string} */
      filters: false,
      // click handlers
      /** @type {function} */
      onReload: null,
      /** @type {function} */
      onExportGpx: null,
      /** @type {function} */
      onExportKml: null,
      /** @type {function} */
      onImportGpx: null,
      /** @type {function} */
      onTrackEdit: null
    });
    this.setClickHandlers();
    /** @type HTMLSelectElement */
    const listEl = document.querySelector('#track');
    this.importEl = document.querySelector('#input-file');
    this.editEl = this.getBoundElement('onTrackEdit');
    this.select = new uSelect(listEl);
    this.state = state;
    this.timerId = 0;
  }

  /**
   * @return {TrackViewModel}
   */
  init() {
    this.setObservers();
    this.bindAll();
    return this;
  }

  setObservers() {
    this.onChanged('trackList', (list) => { this.select.setOptions(list); });
    this.onChanged('currentTrackId', (listValue) => {
      this.onTrackSelect(listValue);
    });
    this.onChanged('inputFile', (file) => {
      if (file) { this.onImport(); }
    });
    this.onChanged('autoReload', (reload) => {
      this.autoReload(reload);
    });
    this.onChanged('showLatest', (showLatest) => {
      this.state.showLatest = showLatest;
      this.onReload(true);
    });
    uObserve.observe(this.state.filter, 'providers', (providers) => {
      this.onTrackFilter("provider", "in", providers);
    });
    for (const attr of ["timestamp", "altitude"]) {
      uObserve.observe(this.state.filter, attr + "Min", (minValue) => {
        this.onTrackFilter(attr, "geq", minValue);
      });
      uObserve.observe(this.state.filter, attr + "Max", (maxValue) => {
        this.onTrackFilter(attr, "leq", maxValue);
      });
    }
    this.state.onChanged('currentUser', (user) => {
      if (user) {
        this.loadTrackList();
        const isEditable = auth.user && (auth.isAdmin || auth.user.id === user.id);
        if (isEditable) {
          TrackViewModel.setMenuVisible(this.editEl, true);
        }
      } else {
        this.model.currentTrackId = '';
        this.model.trackList = [];
        TrackViewModel.setMenuVisible(this.editEl, false);
      }
    });
    this.state.onChanged('currentTrack', (track) => {
      this.renderSummary(track);
      if (track) {
        uObserve.observe(track, 'positions', () => {
          this.renderSummary(track);
        });
      }
      this.renderFilters(track);
      if (track) {
        uObserve.observe(track, 'positions', () => {
          this.renderFilters(track);
        });
      }
    });
    this.state.onChanged('showAllUsers', (showAll) => {
      if (showAll) {
        this.loadAllUsersPosition();
      }
    });
    config.onChanged('interval', () => {
      if (this.timerId) {
        this.stopAutoReload();
        this.startAutoReload();
      }
    });
    this.state.onChanged('history', (history) => {
      if (history && !history.userId && history.trackId) {
        this.model.currentTrackId = history.trackId.toString();
      }
    });
  }

  setClickHandlers() {
    this.model.onReload = () => this.onReload();
    const exportCb = (type) => () => {
      if (this.state.currentTrack) {
        this.state.currentTrack.export(type);
      }
    };
    this.model.onExportGpx = exportCb('gpx');
    this.model.onExportKml = exportCb('kml');
    this.model.onImportGpx = () => this.importEl.click();
    this.model.onTrackEdit = () => this.showDialog();
  }

  /**
   * Reload or update track view
   * @param {boolean} clear Reload if true, update current track otherwise
   */
  onReload(clear = false) {
    if (this.state.showLatest) {
      if (this.state.showAllUsers) {
        this.loadAllUsersPosition();
      } else if (this.state.currentUser) {
        this.onUserLastPosition();
      }
    } else if (this.state.currentTrack instanceof uTrack) {
      this.onTrackUpdate(clear);
    } else if (this.state.currentTrack instanceof uPositionSet) {
      this.state.currentTrack = null;
    } else if (this.state.currentUser) {
      this.loadTrackList();
    }
  }

  /**
   * Handle import
   */
  onImport() {
    const form = this.importEl.parentElement;
    const sizeMax = form.elements['MAX_FILE_SIZE'].value;
    if (this.importEl.files && this.importEl.files.length === 1 && this.importEl.files[0].size > sizeMax) {
      uAlert.error($._('isizefailure', sizeMax));
      return;
    }
    if (!auth.isAuthenticated) {
      uAlert.error($._('notauthorized'));
      return;
    }
    this.state.jobStart();
    uTrack.import(form, auth.user)
      .then((trackList) => {
        if (trackList.length) {
          if (trackList.length > 1) {
            uAlert.toast($._('imultiple', trackList.length));
          }
          this.model.trackList = trackList.concat(this.model.trackList);
          this.model.currentTrackId = trackList[0].listValue;
        }
      })
      .catch((e) => uAlert.error(`${$._('actionfailure')}\n${e.message}`, e))
      .finally(() => {
        this.model.inputFile = '';
        this.state.jobStop();
      });
  }

  /**
   * Handle track change
   * @param {string} listValue Track list selected option
   */
  onTrackSelect(listValue) {
    /** @type {(uTrack|undefined)} */
    const track = this.model.trackList.find((_track) => _track.listValue === listValue);
    if (!track) {
      this.state.currentTrack = null;
    } else if (!track.isEqualTo(this.state.currentTrack)) {
      this.state.jobStart();
      track.fetchPositions().then(() => {
        console.log(`currentTrack id: ${track.id}, loaded ${track.length} positions`);
        this.state.currentTrack = track;
        if (this.model.showLatest) {
          this.model.showLatest = false;
        }
      })
        .catch((e) => { uAlert.error(`${$._('actionfailure')}\n${e.message}`, e); })
        .finally(() => this.state.jobStop());
    }
  }

  /**
   * Handle track update
   * @param {boolean=} clear
   */
  onTrackUpdate(clear) {
    if (clear) {
      this.state.currentTrack.clear();
    }
    this.state.currentTrack.fetchPositions()
      .catch((e) => { uAlert.error(`${$._('actionfailure')}\n${e.message}`, e); });
  }

  /**
   * Handle track filtering
   * @param {string} attr
   * @param {string} operator
   * @param {string|array|number} filter
   */
  onTrackFilter(attr, operator, filter) {
    this.state.jobStart();
    const track = this.state.currentTrack;
    this.state.currentTrack = null;
    track.positions.forEach(position => position.filter(attr, operator, filter));
    track.recalculatePositionsVisible();
    this.state.currentTrack = track;
    this.state.jobStop();
  }

  /**
   * Handle user last position request
   */
  onUserLastPosition() {
    this.state.currentUser.fetchLastPosition()
      .then((_track) => {
        if (_track) {
          if (!this.model.trackList.find((listItem) => listItem.listValue === _track.listValue)) {
            this.model.trackList.unshift(_track);
          }
          this.state.currentTrack = _track;
          this.model.currentTrackId = _track.listValue;
        }
      })
      .catch((e) => { uAlert.error(`${$._('actionfailure')}\n${e.message}`, e); });
  }

  /**
   * Handle last position of all users request
   */
  loadAllUsersPosition() {
    this.state.jobStart();
    uPositionSet.fetchLatest()
      .then((_track) => {
        if (_track) {
          this.model.trackList = [];
          this.model.currentTrackId = '';
          this.state.currentTrack = _track;
        }
      })
      .catch((e) => { uAlert.error(`${$._('actionfailure')}\n${e.message}`, e); })
      .finally(() => this.state.jobStop());
  }

  loadTrackList() {
    this.state.jobStart();
    uTrack.fetchList(this.state.currentUser)
      .then((_tracks) => {
        this.model.trackList = _tracks;
        if (_tracks.length) {
          if (this.state.showLatest) {
            this.onUserLastPosition();
          } else if (this.state.history) {
            this.model.currentTrackId = this.state.history.trackId.toString();
          } else {
            this.model.currentTrackId = _tracks[0].listValue;
          }
        } else {
          this.model.currentTrackId = '';
        }
      })
      .catch((e) => { uAlert.error(`${$._('actionfailure')}\n${e.message}`, e); })
      .finally(() => this.state.jobStop());
  }

  showDialog() {
    const vm = new TrackDialogModel(this);
    vm.init();
  }

  onTrackDeleted() {
    let index = this.model.trackList.indexOf(this.state.currentTrack);
    this.state.currentTrack = null;
    if (index !== -1) {
      this.model.trackList.splice(index, 1);
      if (this.model.trackList.length) {
        if (index >= this.model.trackList.length) {
          index = this.model.trackList.length - 1;
        }
        this.model.currentTrackId = this.model.trackList[index].listValue;
      } else {
        this.model.currentTrackId = '';
      }
    }
  }

  /**
   * @param {boolean} start
   */
  autoReload(start) {
    if (start) {
      this.startAutoReload();
    } else {
      this.stopAutoReload();
    }
  }

  startAutoReload() {
    this.timerId = setInterval(() => this.onReload(), config.interval * 1000);
  }

  stopAutoReload() {
    clearInterval(this.timerId);
    this.timerId = 0;
  }

  /**
   * @param {HTMLElement} el
   * @param {boolean} visible
   */
  static setMenuVisible(el, visible) {
    if (el) {
      if (visible) {
        el.classList.remove('menu-hidden');
      } else {
        el.classList.add('menu-hidden');
      }
    }
  }

  renderSummary(track) {
    if (!track || !track.hasPositionsVisible) {
      this.model.summary = '';
      return;
    }
    if (this.state.showLatest) {
      const today = new Date();
      const date = new Date(last.timestamp * 1000);
      const dateTime = uUtils.getTimeString(date);
      const dateString = (date.toDateString() !== today.toDateString()) ? `${dateTime.date}<br>` : '';
      const timeString = `${dateTime.time}<span style="font-weight:normal">${dateTime.zone}</span>`;
      this.model.summary = `
        <div class="menu-title">${$._('latest')}:</div>
        ${dateString}
        ${timeString}`;
    } else {
      let summary = `
        <div class="menu-title">${$._('summary')}</div>
        <div><img class="icon" alt="${$._('tdistance')}" title="${$._('tdistance')}" src="images/distance.svg"> ${$.getLocaleDistanceMajor(track.metersTotalVisible, true)}</div>
        <div><img class="icon" alt="${$._('ttime')}" title="${$._('ttime')}" src="images/time.svg"> ${$.getLocaleDuration(track.secondsTotalVisible)}</div>`;
      if (track.secondsTotalVisible > 0) {
        summary += `
          <div><img class="icon" alt="${$._('aspeed')}" title="${$._('aspeed')}" src="images/speed.svg"><b>&#10547;</b> ${$.getLocaleSpeed(track.metersTotalVisible / track.secondsTotalVisible, true)}</div>`;
      }
      if (track.hasSpeedsVisible) {
        summary += `<div><img class="icon" alt="${$._('speed')}" title="${$._('speed')}" src="images/speed.svg"><b>&#10138;</b> ${$.getLocaleSpeed(track.speedMaxVisible, true)}</div>`;
      }
      if (track.hasAltitudesVisible) {
        let altitudes = `${$.getLocaleAltitude(track.altitudeMaxVisible, true)}`;
        if (track.altitudeMinVisible !== track.altitudeMaxVisible) {
          altitudes = `${$.getLocaleAltitude(track.altitudeMinVisible)}&ndash;${altitudes}`;
        }
        summary += `<div><img class="icon" alt="${$._('altitude')}" title="${$._('altitude')}" src="images/altitude.svg"> ${altitudes}</div>`;
      }
      this.model.summary = summary;
    }
  }

  renderFilters(track) {
    if (!track || !track.hasPositions) {
      return;
    }
    if (this.state.showLatest) {
      this.model.filters = "";
    } else {
      //
      // CREATE FILTER HTML ELEMENTS
      // they need to be rerendered on every change of current track
      // because filter options and limits can change
      //
      let filters = `
        <div class="menu-title">${$._('filters')}</div>`;
      // providersFilter
      filters = `
        <label for="filter-provider">${$._('providers')}</label><br>`;
      for (const provider of track.providers) {
        filters += `<input id="filter-provider-${provider}" type="checkbox" data-bind="providersFilter_${provider}"`;
        filters += (this.model["providersFilter_" + provider]) ? " checked" : "";
        filters += `> <label for="filter-provider-${provider}">${provider}</label><br>`;
      }
      // datetime: timestampFilter
      for (const attr of ["timestamp"]) {
        const minValue = (track[attr + "Min"]) ? uUtils.getTimeString(new Date(track[attr + "Min"] * 1000)) : null;
        const maxValue = (track[attr + "Max"]) ? uUtils.getTimeString(new Date(track[attr + "Max"] * 1000)) : null;
        for (const minmax of ["min", "max"]) {
          const filterValue = (this.model[attr + "Filter_" + minmax]) ? uUtils.getTimeString(new Date(this.model[attr + "Filter_" + minmax])) : null;
          filters += `
            <label for="filter-${attr}-${minmax}">${$._(attr)} (${$._(minmax)})</label><br>
            <input id="filter-${attr}-${minmax}" type="datetime-local" step=1 data-bind="${attr}Filter_${minmax}"`;
          filters += (minValue) ? ` min="${minValue.date}T${minValue.time}"` : "";
          filters += (maxValue) ? ` max="${maxValue.date}T${maxValue.time}"` : "";
          filters += (filterValue) ? ` value="${filterValue.date}T${filterValue.time}"` : "";
          filters += `>`;
        }
      }
      // integer: altitudeFilter
      for (const attr of ["altitude"]) {
        const minValue = (track[attr + "Min"]) ? track[attr + "Min"] : null;
        const maxValue = (track[attr + "Max"]) ? track[attr + "Max"] : null;
        for (const minmax of ["min", "max"]) {
          const filterValue = this.model[attr + "Filter_" + minmax];
          filters += `
            <label for="filter-${attr}-${minmax}">${$._(attr)} (${$._(minmax)})</label><br>
            <input id="filter-${attr}-${minmax}" type="number" step=1 data-bind="${attr}Filter_${minmax}" min="${minValue}" max="${maxValue}" value="${filterValue}">`;
        }
      }
      this.model.filters = filters;
      //
      // BIND AND OBSERVE FILTER HTML ELEMENTS
      // they need to be refreshed on every change of the current track
      // because the html elements are refreshed above and
      // because some of the html elements correlate to filter options
      //
      // always consists of the same steps
      // 1. make sure there is an unobserved model property
      // 2. bind the model property to new html element created
      // 3. observe the model property
      // 4. initialize the property after first creation
      //
      // providersFilter
      for (const provider of track.providers) {
        if (!this.model.hasOwnProperty("providersFilter_" + provider)) {
          this.model["providersFilter_" + provider] = null;
        } else {
          uObserve.unobserveAll(this.model, "providersFilter_" + provider);
        }
        this.bind("providersFilter_" + provider);
        this.onChanged("providersFilter_" + provider, (toggle) => {
          if (toggle) {
            if (!this.state.filter.providers.includes(provider)) {
              this.state.filter.providers.push(provider);
            }
          } else {
            if (this.state.filter.providers.includes(provider)) {
              const index = this.state.filter.providers.indexOf(provider);
              if (index > -1) {
                this.state.filter.providers.splice(index, 1);
              }
            }
          }
        });
        if (this.model["providersFilter_" + provider] === null) {
          this.model["providersFilter_" + provider] = true;
        }
      }
      // datetime: timestampFilter
      for (const attr of ["timestamp"]) {
        for (const minmax of ["min", "max"]) {
          const minMax = minmax.charAt(0).toUpperCase() + minmax.slice(1);
          const attrNameState = attr + minMax;
          const attrNameModel = attr + "Filter_" + minmax;
          if (!this.model.hasOwnProperty(attrNameModel)) {
            this.model[attrNameModel] = null;
          } else {
            uObserve.unobserveAll(this.model, attrNameModel);
          }
          this.bind(attrNameModel);
          this.onChanged(attrNameModel, (value) => {
            this.state.filter[attrNameState] = (value) ? Math.floor(new Date(value).getTime() / 1000) : null;
          });
          if (this.model[attrNameModel] === null) {
            const value = (track[attrNameState]) ? uUtils.getTimeString(new Date(track[attrNameState] * 1000)) : null;
            this.model[attrNameModel] = (value) ? value.date + "T" + value.time : null;
          }
        }
      }
      // number (integer or float): altitudeFilter
      for (const attr of ["altitude"]) {
        for (const minmax of ["min", "max"]) {
          const minMax = minmax.charAt(0).toUpperCase() + minmax.slice(1);
          const attrNameState = attr + minMax;
          const attrNameModel = attr + "Filter_" + minmax;
          if (!this.model.hasOwnProperty(attrNameModel)) {
            this.model[attrNameModel] = null;
          } else {
            uObserve.unobserveAll(this.model, attrNameModel);
          }
          this.bind(attrNameModel);
          this.onChanged(attrNameModel, (value) => {
            this.state.filter[attrNameState] = value;
          });
          if (this.model[attrNameModel] === null) {
            this.model[attrNameModel] = track[attrNameState];
          }
        }
      }
    }
  }

}
