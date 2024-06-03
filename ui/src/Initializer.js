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

import Config from './Config.js';
import Http from './Http.js';
import Locale from './Locale.js';
import Session from './Session.js';

/**
 * @Class Initializer
 * @property {Session} auth
 * @property {Config} config
 * @property {Locale} lang
 */
export class Initializer {

  constructor() {
    this.auth = new Session();
    this.config = new Config();
    this.lang = new Locale();
  }

  /**
   * @return {Promise<void, Error>}
   */
  initialize() {
    const authPromise = Http.get('api/session');
    const configPromise = Http.get('api/config');
    const langPromise = Http.get('api/locales');
    return Promise.allSettled([ authPromise, configPromise, langPromise ])
      .then((result) => {
        if (result[1].status === 'rejected' || result[2].status === 'rejected') {
          let reason = '';
          if (result[1].reason) {
            reason += result[1].reason;
          }
          if (result[2].reason) {
            reason += result[2].reason;
          }
          throw new Error(`Corrupted initialization data (${reason})`);
        }
        if (result[0].status === 'fulfilled') {
          this.auth.load(result[0].value);
        }
        this.config.load(result[1].value);
        this.lang.init(this.config, result[2].value);
      });
  }

  static waitForDom() {
    return new Promise((resolve) => {
      if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(resolve, 1);
      } else {
        document.addEventListener('DOMContentLoaded', resolve);
      }
    });
  }
}

export const initializer = new Initializer();
export const config = initializer.config;
export const lang = initializer.lang;
export const auth = initializer.auth;
