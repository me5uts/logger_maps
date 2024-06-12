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

import Http from './Http.js';
import ListItem from './ListItem.js';
import Position from './Position.js';

/**
 * Set of unrelated positions
 * @class PositionSet
 * @property {Position[]} positions
 */
export default class PositionSet extends ListItem {

  constructor() {
    super();
    this.positions = [];
  }

  clear() {
    this.positions.length = 0;
  }

  /**
   * @return {number}
   */
  get length() {
    return this.positions.length;
  }

  /**
   * @return {boolean}
   */
  get hasPositions() {
    return this.positions.length > 0;
  }

  // eslint-disable-next-line no-unused-vars,class-methods-use-this
  isLastPosition(id) {
    return true;
  }

  // eslint-disable-next-line no-unused-vars,class-methods-use-this
  isFirstPosition(id) {
    return true;
  }

  /**
   * Get track data from json
   * @param {Object[]} posArr Positions data
   * @param {boolean=} isUpdate If true append to old data
   */
  fromJson(posArr, isUpdate = false) {
    let positions = [];
    if (isUpdate) {
      positions = this.positions;
    } else {
      this.clear();
    }
    for (const pos of posArr) {
      positions.push(Position.fromJson(pos));
    }
    // update at the end to avoid observers update invidual points
    this.positions = positions;
  }

  /**
   * Fetch latest position of each user.
   * @return {Promise<void, Error>}
   */
  fetchLatest() {
    this.clear();
    return Http.get('api/users/position').then((_positions) => {
      this.fromJson(_positions);
    });
  }

  /**
   * Fetch latest position of each user.
   * @return {Promise<?PositionSet, Error>}
   */
  static fetchLatest() {
    const set = new PositionSet();
    return set.fetchLatest().then(() => {
      if (set.length) {
        return set;
      }
      return null;
    });
  }

}
