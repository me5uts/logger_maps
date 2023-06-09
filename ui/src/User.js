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
import Track from './Track.js';

/**
 * @class User
 * @property {number} id
 * @property {string} login
 * @property {boolean} isAdmin
 */
export default class User extends ListItem {
  /**
   * @param {number} id
   * @param {string} login
   * @param {boolean=} isAdmin
   */
  constructor(id, login, isAdmin = false) {
    super();
    if (!Number.isSafeInteger(id) || id <= 0) {
      throw new Error('Invalid argument for user constructor');
    }
    this.id = id;
    this.login = login;
    this.isAdmin = isAdmin;
    this.listItem(id, login);
  }

  /**
   * @param {User} user
   * @return {boolean}
   */
  isEqualTo(user) {
    return !!user && user.id === this.id;
  }

  /**
   * @return {Promise<Track, Error>}
   */
  fetchLastPosition() {
    return Track.fetchLatest(this);
  }

  /**
   * @throws
   * @return {Promise<User[], Error>}
   */
  static fetchList() {
    return Http.get('utils/getusers.php').then((_users) => {
      const users = [];
      for (const user of _users) {
        users.push(new User(user.id, user.login, user.isAdmin));
      }
      return users;
    });
  }

  delete() {
    return User.update({
      action: 'delete',
      login: this.login
    });
  }

  /**
   *
   * @param {string} login
   * @param {string} password
   * @param {boolean} isAdmin
   * @return {Promise<User>}
   */
  static add(login, password, isAdmin) {
    return User.update({
      action: 'add',
      login: login,
      pass: password,
      admin: isAdmin
    }).then((user) => new User(user.id, login, isAdmin));
  }

  /**
   * @param {Object} data
   * @return {Promise<*, Error>}
   */
  static update(data) {
    return Http.post('utils/handleuser.php', data);
  }

  /**
   * @param {string} password New password
   * @param {string} oldPassword Current password
   * @return {Promise<void, Error>}
   */
  setPassword(password, oldPassword) {
    return Http.post('utils/changepass.php',
      {
        login: this.login,
        pass: password,
        oldpass: oldPassword
      });
  }

  /**
   * @param {boolean} isAdmin
   * @param {string|null} password
   * @return {Promise<void, Error>}
   */
  modify(isAdmin, password = null) {
    const data = {
      action: 'update',
      login: this.login,
      admin: isAdmin
    };
    if (password) {
      data.pass = password;
    }
    return User.update(data)
      .then(() => { this.isAdmin = isAdmin; });
  }

}
