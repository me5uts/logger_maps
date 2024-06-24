/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { lang as $, auth, config } from '../Initializer.js';
import Alert from '../Alert.js';
import Observer from '../Observer.js';
import PositionSet from '../PositionSet.js';
import Select from '../Select.js';
import Track from '../Track.js';
import TrackDialogModel from './TrackDialogModel.js';
import Utils from '../Utils.js';
import ViewModel from '../ViewModel.js';

/**
 * @class TrackViewModel
 */
export default class TrackViewModel extends ViewModel {

  /**
   * @param {State} state
   */
  constructor(state) {
    super({
      /** @type {Track[]} */
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
    this.select = new Select(listEl);
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
      this.renderSummary();
      if (track) {
        Observer.observe(track, 'positions', () => {
          this.renderSummary();
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
    } else if (this.state.currentTrack instanceof Track) {
      this.onTrackUpdate(clear);
    } else if (this.state.currentTrack instanceof PositionSet) {
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
      Alert.error($._('isizefailure', sizeMax));
      return;
    }
    if (!auth.isAuthenticated) {
      Alert.error($._('notauthorized'));
      return;
    }
    this.state.jobStart();
    Track.import(form, auth.user)
      .then((trackList) => {
        if (trackList.length) {
          if (trackList.length > 1) {
            Alert.toast($._('imultiple', trackList.length));
          }
          this.model.trackList = trackList.concat(this.model.trackList);
          this.model.currentTrackId = trackList[0].listValue;
        }
      })
      .catch((e) => Alert.error(`${$._('actionfailure')}\n${e.message}`, e))
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
    /** @type {(Track|undefined)} */
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
        .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); })
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
      .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
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
      .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
  }

  /**
   * Handle last position of all users request
   */
  loadAllUsersPosition() {
    this.state.jobStart();
    PositionSet.fetchLatest()
      .then((_track) => {
        if (_track) {
          this.model.trackList = [];
          this.model.currentTrackId = '';
          this.state.currentTrack = _track;
        }
      })
      .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); })
      .finally(() => this.state.jobStop());
  }

  loadTrackList() {
    this.state.jobStart();
    Track.fetchList(this.state.currentUser)
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
      .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); })
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

  renderSummary() {
    const track = this.state.currentTrack;
    if (!track || !track.hasPositions) {
      this.model.summary = '';
      return;
    }
    const last = track.positions[track.length - 1];

    if (this.state.showLatest) {
      const today = new Date();
      const date = new Date(last.timestamp * 1000);
      const dateTime = Utils.getTimeString(date);
      const dateString = (date.toDateString() !== today.toDateString()) ? `${dateTime.date}<br>` : '';
      const timeString = `${dateTime.time}<span style="font-weight:normal;">${dateTime.zone}</span>`;
      this.model.summary = `
        <div class="menu-title">${$._('latest')}:</div>
        ${dateString}
        ${timeString}`;
    } else {
      let summary = `
        <div class="menu-title">${$._('summary')}</div>
        <div><img class="icon" alt="${$._('tdistance')}" title="${$._('tdistance')}" src="images/distance.svg"> ${$.getLocaleDistanceMajor(last.totalMeters, true)}</div>
        <div><img class="icon" alt="${$._('ttime')}" title="${$._('ttime')}" src="images/time.svg"> ${$.getLocaleDuration(last.totalSeconds)}</div>`;
      if (last.totalSeconds > 0) {
        summary += `
          <div><img class="icon" alt="${$._('aspeed')}" title="${$._('aspeed')}" src="images/speed.svg"><b>&#10547;</b> ${$.getLocaleSpeed(last.totalMeters / last.totalSeconds, true)}</div>`;
      }
      if (track.hasSpeeds) {
        summary += `<div><img class="icon" alt="${$._('speed')}" title="${$._('speed')}" src="images/speed.svg"><b>&#10138;</b> ${$.getLocaleSpeed(track.maxSpeed, true)}</div>`;
      }
      if (track.hasAltitudes) {
        let altitudes = `${$.getLocaleAltitude(track.maxAltitude, true)}`;
        if (track.minAltitude !== track.maxAltitude) {
          altitudes = `${$.getLocaleAltitude(track.minAltitude)}&ndash;${altitudes}`;
        }
        summary += `<div><img class="icon" alt="${$._('altitude')}" title="${$._('altitude')}" src="images/altitude.svg"> ${altitudes}</div>`;
      }
      this.model.summary = summary;
    }
  }

}
