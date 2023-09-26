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

import { lang as $, auth, config } from '../Initializer.js';
import Router from '../Router.js';
import ViewModel from '../ViewModel.js';


export default class LoginViewModel extends ViewModel {

  /**
   * @param {State} state
   */
  constructor(state) {
    super({
      error: '',
      user: '',
      password: '',
      onLoginSubmit: null,
      onLoginCancel: null
    });
    this.state = state;
    this.model.onLoginSubmit = () => this.onSubmit();
    this.model.onLoginCancel = () => this.onCancel();
  }

  /**
   * @return {LoginViewModel}
   */
  init() {
    this.loadHtmlIntoBody();
    this.bindAll();
    return this;
  }

  onCancel() {
    console.log('login cancel');
    Router.initView()
  }

  onSubmit() {
    if (this.validate()) {
      auth.login(this.model.user, this.model.password)
        .then(() => {
          this.model.error = '';
          console.log(`login successful: ${this.model.user}:${this.model.password}`);
          Router.initView();
        })
        .catch(() => { this.model.error = $._('authfail'); });
    }
  }

  validate() {
    const form = document.querySelector('form');
    return form.checkValidity();
  }




  getHtml() {
    let cancelButton = '';
    if (!config.requireAuth) {
      cancelButton = `<div data-bind="onLoginCancel" id="cancel">${$._('cancel')}</div>`;
    }

    return `
    <div id="login">
      <div id="title">${$._('title')}</div>
      <div id="subtitle">${$._('private')}</div>
      <form>
        <label for="login-user">${$._('username')}</label><br>
        <input id="login-user" data-bind="user" type="text" name="user" required><br>
        <label for="login-pass">${$._('password')}</label><br>
        <input id="login-pass" data-bind="password" type="password" name="pass" required><br>
        <br>
        <button id="login-button" data-bind="onLoginSubmit">${$._('login')}</button>
        ${cancelButton}
      </form>
      <div id="error" data-bind="error"></div>
    </div>`;
  }

}
