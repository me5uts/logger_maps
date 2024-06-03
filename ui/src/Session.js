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

  /** @var {boolean} */
  #isAdmin;
  /** @var {boolean} */
  #isAuthenticated;
  /** @var {?User} */
  #user;

  constructor() {
    this.init();
  }

  init() {
    this.#isAdmin = false;
    this.#isAuthenticated = false;
    this.#user = null;
  }

  /**
   * @param {string} login
   * @param {string} password
   * @return {Promise<Object, Error>}
   */
  login(login, password) {
    return Http.post('api/session', {
      login: login,
      password: password
    }).then((data) => this.load(data));
  }

  logout() {
    this.init();
    return Http.delete('api/session', {
      route: 'session',
      method: 'delete'
    });
  }

  /**
   * @param {User} user
   */
  set user(user) {
    if (user) {
      this.#user = user;
      this.#isAuthenticated = true;
    } else {
      this.#user = null;
      this.#isAuthenticated = false;
      this.#isAdmin = false;
    }
  }

  /**
   * @param {boolean} isAdmin
   */
  set isAdmin(isAdmin) {
    if (!this.#user) {
      throw new Error('No authenticated user');
    }
    this.#isAdmin = isAdmin;
  }

  /**
   * @return {boolean}
   */
  get isAdmin() {
    return this.#isAdmin;
  }

  /**
   * @return {boolean}
   */
  get isAuthenticated() {
    return this.#isAuthenticated;
  }

  /**
   * @return {?User}
   */
  get user() {
    return this.#user;
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
