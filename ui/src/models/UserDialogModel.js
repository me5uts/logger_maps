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
import Alert from '../Alert.js';
import Dialog from '../Dialog.js';
import User from '../User.js';
import Utils from '../Utils.js';
import ViewModel from '../ViewModel.js';

export default class UserDialogModel extends ViewModel {

  /**
   * @param {UserViewModel} viewModel
   * @param {string} type
   */
  constructor(viewModel, type) {
    super({
      onUserDelete: null,
      onUserUpdate: null,
      onPassChange: null,
      onUserAdd: null,
      onCancel: null,
      passVisibility: false,
      login: null,
      password: null,
      password2: null,
      oldPassword: null,
      admin: false
    });
    this.user = viewModel.state.currentUser;
    this.type = type;
    this.userVM = viewModel;
    this.model.onUserDelete = () => this.onUserDelete();
    this.model.onUserUpdate = () => this.onUserUpdate();
    this.model.onPassChange = () => this.onPassChange();
    this.model.onUserAdd = () => this.onUserAdd();
    this.model.onCancel = () => this.onCancel();
  }

  init() {
    const html = this.getHtml();
    this.dialog = new Dialog(html);
    this.dialog.show();
    this.bindAll(this.dialog.element);
    const passInput = this.getBoundElement('passInput');
    if (passInput) {
      this.onChanged('passVisibility', () => {
        if (passInput.style.display === 'none') {
          passInput.style.display = 'block';
        } else {
          passInput.style.display = 'none';
        }
      });
    }
  }

  onUserDelete() {
    if (Dialog.isConfirmed($._('userdelwarn', Utils.htmlEncode(this.user.login)))) {
      this.user.delete().then(() => {
        this.userVM.onUserDeleted();
        this.dialog.destroy();
      }).catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
    }
  }

  onUserUpdate() {
    if (this.validate()) {
      const password = this.model.passVisibility ? this.model.password : null;
      this.user.modify(this.model.admin, password)
        .then(() => this.dialog.destroy())
        .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
    }
  }

  onPassChange() {
    this.model.passVisibility = true;
    if (this.validate()) {
      auth.user.setPassword(this.model.password, this.model.oldPassword)
        .then(() => this.dialog.destroy())
        .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
    }
  }

  onUserAdd() {
    this.model.passVisibility = true;
    if (this.validate()) {
      User.add(this.model.login, this.model.password, this.model.admin).then((user) => {
        this.userVM.onUserAdded(user);
        this.dialog.destroy();
      }).catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
    }
  }

  onCancel() {
    this.dialog.destroy();
  }

  /**
   * Validate form
   * @return {boolean} True if valid
   */
  validate() {
    if (this.type === 'add') {
      if (!this.model.login) {
        Alert.error($._('allrequired'));
        return false;
      }
    } else if (this.type === 'pass') {
      if (!this.model.oldPassword) {
        Alert.error($._('allrequired'));
        return false;
      }
    }
    if (this.model.passVisibility) {
      if (!this.model.password || !this.model.password2) {
        Alert.error($._('allrequired'));
        return false;
      }
      if (this.model.password !== this.model.password2) {
        Alert.error($._('passnotmatch'));
        return false;
      }
      if (!config.validPassStrength(this.model.password)) {
        Alert.error($.getLocalePassRules());
        return false;
      }
    }
    return true;
  }

  /**
   * @return {string}
   */
  getHtml() {
    let deleteButton = '';
    let header = '';
    let observer;
    let fields;
    switch (this.type) {
      case 'add':
        observer = 'onUserAdd';
        header = `<label><b>${$._('username')}</b></label>
        <input type="text" placeholder="${$._('usernameenter')}" name="login" data-bind="login" required autofocus>`;
        fields = `<label><b>${$._('password')}</b></label>
        <input type="password" placeholder="${$._('passwordenter')}" name="password" data-bind="password" required>
        <label><b>${$._('passwordrepeat')}</b></label>
        <input type="password" placeholder="${$._('passwordenter')}" name="password2" data-bind="password2" required>
        <label><b>${$._('admin')}</b></label>
        <input type="checkbox" name="admin" data-bind="admin">`;
        break;
      case 'edit':
        observer = 'onUserUpdate';
        deleteButton = `<div class="red-button button-resolve"><b><a data-bind="onUserDelete">${$._('deluser')}</a></b></div>
        <div>${$._('editinguser', `<b>${Utils.htmlEncode(this.user.login)}</b>`)}</div>
        <div style="clear: both; padding-bottom: 1em;"></div>`;
        fields = `<label><b>${$._('changepass')}</b></label>
        <input type="checkbox" name="changepass" data-bind="passVisibility"><br>
        <div style="display: none;" data-bind="passInput">
          <label><b>${$._('password')}</b></label>
          <input type="password" placeholder="${$._('passwordenter')}" name="password" data-bind="password" required autofocus>
          <label><b>${$._('passwordrepeat')}</b></label>
          <input type="password" placeholder="${$._('passwordenter')}" name="password2" data-bind="password2" required>
        </div>
        <label><b>${$._('admin')}</b></label>
        <input type="checkbox" name="admin" data-bind="admin" ${this.user.isAdmin ? 'checked' : ''}>`;
        break;
      case 'pass':
        observer = 'onPassChange';
        fields = `<label><b>${$._('oldpassword')}</b></label>
        <input type="password" placeholder="${$._('passwordenter')}" name="old-password" data-bind="oldPassword" required autofocus>
        <label><b>${$._('newpassword')}</b></label>
        <input type="password" placeholder="${$._('passwordenter')}" name="password" data-bind="password" required>
        <label><b>${$._('newpasswordrepeat')}</b></label>
        <input type="password" placeholder="${$._('passwordenter')}" name="password2" data-bind="password2" required>`;
        break;
      default:
        throw new Error(`Unknown dialog type: ${this.type}`);
    }
    return `${deleteButton}
      <form id="userForm">
        ${header}
        ${fields}
        <div class="buttons">
          <button class="button-reject" type="button" data-bind="onCancel">${$._('cancel')}</button>
          <button class="button-resolve" type="submit" data-bind="${observer}">${$._('submit')}</button>
        </div>
      </form>`;
  }

}
