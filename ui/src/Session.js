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
import User from './User.js';

export default class Session {

  constructor() {
    this.init();
  }

  init() {
    /** @type {boolean} */
    this._isAdmin = false;
    /** @type {boolean} */
    this._isAuthenticated = false;
    /** @type {?User} */
    this._user = null;
  }

  /**
   * @param {string} user
   * @param {string} password
   * @return {Promise<Object, Error>}
   */
  login(user, password) {
    return Http.post('index.php', {
      route: 'session',
      method: 'post',
      user: user,
      pass: password
    }).then((data) => this.load(data));
  }

  logout() {
    this.init();
    return Http.post('index.php', {
      route: 'session',
      method: 'delete'
    });
  }

  /**
   * @param {User} user
   */
  set user(user) {
    if (user) {
      this._user = user;
      this._isAuthenticated = true;
    } else {
      this._user = null;
      this._isAuthenticated = false;
      this._isAdmin = false;
    }
  }

  /**
   * @param {boolean} isAdmin
   */
  set isAdmin(isAdmin) {
    if (!this._user) {
      throw new Error('No authenticated user');
    }
    this._isAdmin = isAdmin;
  }

  /**
   * @return {boolean}
   */
  get isAdmin() {
    return this._isAdmin;
  }

  /**
   * @return {boolean}
   */
  get isAuthenticated() {
    return this._isAuthenticated;
  }

  /**
   * @return {?User}
   */
  get user() {
    return this._user;
  }

  /**
   * Load auth state from data object
   * @param {Object} data
   * @param {boolean} data.isAdmin
   * @param {boolean} data.isAuthenticated
   * @param {?number} data.userId
   * @param {?string} data.userLogin
   */
  load(data) {
    if (data) {
      if (data.isAuthenticated) {
        this.user = new User(data.userId, data.userLogin);
        this.isAdmin = data.isAdmin;
      }
    }
  }
}
