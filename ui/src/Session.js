/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
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
