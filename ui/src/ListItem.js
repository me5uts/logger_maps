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

import Select from './Select.js';

/**
 * @class ListItem
 * @property {string} listValue
 * @property {string} listText
 */
export default class ListItem {

  constructor() {
    this.listValue = Select.allValue;
    this.listText = '-';
  }

  /**
   * @param {string|number} id
   * @param {string|number} value
   */
  listItem(id, value) {
    this.listValue = String(id);
    this.listText = String(value);
  }

  /**
   * @return {string}
   */
  toString() {
    return `[${this.listValue}, ${this.listText}]`;
  }
}
