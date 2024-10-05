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

import uAjax from './ajax.js';
import uPosition from './position.js';
import uPositionSet from './positionset.js';
import uUser from './user.js';
import uUtils from './utils.js';

/**
 * Set of positions representing user's track
 * @class uTrack
 * @property {number} id
 * @property {string} name
 * @property {uUser} user
 * @property {uPosition[]} positions
 * @property {PlotData} plotData
 */
export default class uTrack extends uPositionSet {

  /**
   * @param {number} id
   * @param {string} name
   * @param {uUser} user
   */
  constructor(id, name, user) {
    super();
    if (!Number.isSafeInteger(id) || id <= 0 || !name || !(user instanceof uUser)) {
      throw new Error('Invalid argument for track constructor');
    }
    this.id = id;
    this.name = name;
    this.user = user;
    this.idMin = null;
    this.idMax = null;
    this.speedMin = null;
    this.speedMax = null;
    this.altitudeMin = null;
    this.altitudeMax = null;
    this.metersTotal = null;
    this.secondsTotal = null;
    this.providers = [];
    this.timestampMin = null;
    this.timestampMax = null;
    this.plotDataVisible = [];
    this.idMinVisible = null;
    this.idMaxVisible = null;
    this.speedMinVisible = null;
    this.speedMaxVisible = null;
    this.altitudeMinVisible = null;
    this.altitudeMaxVisible = null;
    this.metersTotalVisible = null;
    this.secondsTotalVisible = null;
    this.providersVisible = [];
    this.timestampMinVisible = null;
    this.timestampMaxVisible = null;
    this.listItem(id, name);
  }

  setName(name) {
    this.name = name;
    this.listText = name;
  }

  clear() {
    super.clear();
    this.clearTrackCounters();
    this.clearTrackCountersVisible();
  }

  clearTrackCounters() {
    this.idMin = null;
    this.idMax = null;
    this.speedMin = null;
    this.speedMax = null;
    this.altitudeMin = null;
    this.altitudeMax = null;
    this.metersTotal = null;
    this.secondsTotal = null;
    this.providers = [];
    this.timestampMin = null;
    this.timestampMax = null;
  }

  clearTrackCountersVisible() {
    this.idMinVisible = null;
    this.idMaxVisible = null;
    this.speedMinVisible = null;
    this.speedMaxVisible = null;
    this.altitudeMinVisible = null;
    this.altitudeMaxVisible = null;
    this.plotDataVisible.length = null;
    this.metersTotalVisible = null;
    this.secondsTotalVisible = null;
    this.providersVisible = [];
    this.timestampMinVisible = null;
    this.timestampMaxVisible = null;
  }

  /**
   * @param {uTrack} track
   * @return {boolean}
   */
  isEqualTo(track) {
    return !!track && track.id === this.id;
  }

  /**
   * @return {boolean}
   */
  get hasPlotDataVisible() {
    return this.plotDataVisible.length > 0;
  }

  /**
   * @return {boolean}
   */
  get hasAltitudesVisible() {
    return this.altitudeMaxVisible !== null;
  }

  /**
   * @return {boolean}
   */
  get hasSpeedsVisible() {
    return this.speedMaxVisible > 0;
  }

  /**
   * Get track data from json
   * @param {Object[]} posArr Positions data
   * @param {boolean=} isUpdate If true append to old data
   */
  fromJson(posArr, isUpdate = false) {
    let positions = [];
    if (isUpdate && this.hasPositions) {
      positions = this.positions;
    } else {
      this.clear();
    }
    for (const pos of posArr) {
      const position = uPosition.fromJson(pos);
      this.calculatePosition(position);
      this.calculatePositionVisible(position);
      positions.push(position);
    }
    // update at the end to avoid observers update invidual points
    this.positions = positions;
  }

  /**
   * @param {number} id
   * @return {boolean}
   */
  isFirstPosition(id) {
    return this.length > 0 && id === 0;
  }

  /**
   * @param {number} id
   * @return {boolean}
   */
  isLastPosition(id) {
    return this.length > 0 && id === this.length - 1;
  }

  /**
   * @param {number} id
   * @return {boolean}
   */
  isFirstPositionVisible(id) {
    return this.lengthVisible > 0 && id === 0;
  }

  /**
   * @param {number} id
   * @return {boolean}
   */
  isLastPositionVisible(id) {
    return this.lengthVisible > 0 && id === this.lengthVisible - 1;
  }

  /**
   * Fetch track positions
   * @return {Promise<void, Error>}
   */
  fetchPositions() {
    const params = {
      userid: this.user.id,
      trackid: this.id
    };
    if (this.idMax) {
      params.afterid = this.idMax;
    }
    return uPositionSet.fetch(params).then((_positions) => {
      this.fromJson(_positions, params.afterid > 0);
    });
  }

  /**
   * Fetch track with latest position of a user.
   * @param {uUser} user
   * @return {Promise<?uTrack, Error>}
   */
  static fetchLatest(user) {
    return this.fetch({
      last: true,
      userid: user.id
    }).then((_positions) => {
      if (_positions.length) {
        const track = new uTrack(_positions[0].trackid, _positions[0].trackname, user);
        track.fromJson(_positions);
        return track;
      }
      return null;
    });
  }

  /**
   * Fetch tracks for given user
   * @throws
   * @param {uUser} user
   * @return {Promise<uTrack[], Error>}
   */
  static fetchList(user) {
    return uAjax.get('utils/gettracks.php', { userid: user.id }).then(
      /**
       * @param {Array.<{id: number, name: string}>} _tracks
       * @return {uTrack[]}
       */
      (_tracks) => {
        const tracks = [];
        for (const track of _tracks) {
          tracks.push(new uTrack(track.id, track.name, user));
        }
        return tracks;
    });
  }

  /**
   * Export to file
   * @param {string} type File type
   */
  export(type) {
    if (this.hasPositions) {
      const url = `utils/export.php?type=${type}&userid=${this.user.id}&trackid=${this.id}`;
      uUtils.openUrl(url);
    }
  }

  /**
   * Imports tracks submited with HTML form and returns last imported track id
   * @param {HTMLFormElement} form
   * @param {uUser} user
   * @return {Promise<uTrack[], Error>}
   */
  static import(form, user) {
    return uAjax.post('utils/import.php', form)
      .then(
        /**
         * @param {Array.<{id: number, name: string}>} _tracks
         * @return {uTrack[]}
         */
        (_tracks) => {
          const tracks = [];
          for (const track of _tracks) {
            tracks.push(new uTrack(track.id, track.name, user));
          }
          return tracks;
      });
  }

  delete() {
    return uTrack.update({
      action: 'delete',
      trackid: this.id
    });
  }

  saveMeta() {
    return uTrack.update({
      action: 'update',
      trackid: this.id,
      trackname: this.name
    });
  }

  /**
   * @param {number} id
   * @return {Promise<{id: number, name: string, userId: number, comment: string|null}, Error>}
   */
  static getMeta(id) {
    return uTrack.update({
      action: 'getmeta',
      trackid: id
    });
  }

  /**
   * Save track data
   * @param {Object} data
   * @return {Promise<void, Error>}
   */
  static update(data) {
    return uAjax.post('utils/handletrack.php', data);
  }

  recalculatePositions() {
    this.clearTrackCounters();
    let previous = null;
    for (const position of this.positions) {
      position.meters = previous ? position.distanceTo(previous) : 0;
      position.seconds = previous ? position.secondsTo(previous) : 0;
      this.calculatePosition(position);
      previous = position;
    }
  }

  recalculatePositionsVisible() {
    this.clearTrackCountersVisible();
    let previous = null;
    for (const position of this.positionsVisible) {
      position.meters = previous ? position.distanceTo(previous) : 0;
      position.seconds = previous ? position.secondsTo(previous) : 0;
      this.calculatePositionVisible(position);
      previous = position;
    }
  }

  /**
   * Calculate position total counters and plot data
   * @param {uPosition} position
   */
  calculatePosition(position) {
    if (this.idMin === null || position.id < this.idMin) {
      this.idMin = position.id;
    }
    if (this.idMax === null || position.id > this.idMax) {
      this.idMax = position.id;
    }
    if (this.timestampMin === null || position.timestamp < this.timestampMin) {
      this.timestampMin = position.timestamp;
    }
    if (this.timestampMax === null || position.timestamp > this.timestampMax) {
      this.timestampMax = position.timestamp;
    }
    this.metersTotal += position.meters;
    this.secondsTotal += position.seconds;
    position.metersTotal = this.metersTotal;
    position.secondsTotal = this.secondsTotal;
    if (position.hasAltitude()) {
      if (this.altitudeMin === null || position.altitude < this.altitudeMin) {
        this.altitudeMin = position.altitude;
      }
      if (this.altitudeMax === null || position.altitude > this.altitudeMax) {
        this.altitudeMax = position.altitude;
      }
    }
    if (position.hasSpeed()) {
      if (this.speedMin === null || position.speed < this.speedMin) {
        this.speedMin = position.speed;
      }
      if (this.speedMax === null || position.speed > this.speedMax) {
        this.speedMax = position.speed;
      }
    }
    if (!this.providers.includes(position.provider)) {
      this.providers.push(position.provider);
    }
  }

  /**
   * Calculate position total counters and plot data
   * @param {uPosition} position
   */
  calculatePositionVisible(position) {
    if (this.idMinVisible === null || position.id < this.idMinVisible) {
      this.idMinVisible = position.id;
    }
    if (this.idMaxVisible === null || position.id > this.idMaxVisible) {
      this.idMaxVisible = position.id;
    }
    if (this.timestampMinVisible === null || position.timestamp < this.timestampMinVisible) {
      this.timestampMinVisible = position.timestamp;
    }
    if (this.timestampMaxVisible === null || position.timestamp > this.timestampMaxVisible) {
      this.timestampMaxVisible = position.timestamp;
    }
    this.metersTotalVisible += position.meters;
    this.secondsTotalVisible += position.seconds;
    position.metersTotalVisible = this.metersTotalVisible;
    position.secondsTotalVisible = this.secondsTotalVisible;
    if (position.hasAltitude()) {
      if (this.altitudeMinVisible === null || position.altitude < this.altitudeMinVisible) {
        this.altitudeMinVisible = position.altitude;
      }
      if (this.altitudeMaxVisible === null || position.altitude > this.altitudeMaxVisible) {
        this.altitudeMaxVisible = position.altitude;
      }
      this.plotDataVisible.push({ x: position.metersTotalVisible, y: position.altitude });
    }
    if (position.hasSpeed()) {
      if (this.speedMinVisible === null || position.speed < this.speedMinVisible) {
        this.speedMinVisible = position.speed;
      }
      if (this.speedMaxVisible === null || position.speed > this.speedMaxVisible) {
        this.speedMaxVisible = position.speed;
      }
    }
    if (!this.providersVisible.includes(position.provider)) {
      this.providersVisible.push(position.provider);
    }
  }
}
