/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { lang as $, auth, config } from '../Initializer.js';
import Alert from '../Alert.js';
import Select from '../Select.js';
import User from '../User.js';
import UserDialogModel from './UserDialogModel.js';
import ViewModel from '../ViewModel.js';

/**
 * @class UserViewModel
 */
export default class UserViewModel extends ViewModel {

  /**
   * @param {State} state
   */
  constructor(state) {
    super({
      /** @type {User[]} */
      userList: [],
      /** @type {string} */
      currentUserId: '0',
      // click handlers
      /** @type {function} */
      onUserEdit: null,
      /** @type {function} */
      onUserAdd: null,
      /** @type {function} */
      onPasswordChange: null
    });
    this.setClickHandlers();
    /** @type HTMLSelectElement */
    const listEl = document.querySelector('#user');
    this.editEl = this.getBoundElement('onUserEdit');
    this.select = new Select(listEl, $._('suser'), `- ${$._('allusers')} -`);
    this.state = state;
  }

  setClickHandlers() {
    this.model.onUserEdit = () => this.showDialog('edit');
    this.model.onUserAdd = () => this.showDialog('add');
    this.model.onPasswordChange = () => this.showDialog('pass');
  }

  /**
   * @return {UserViewModel}
   */
  init() {
    this.setObservers(this.state);
    this.bindAll();
    return this;
  }

  start() {
    if (!config.publicTracks && auth.isAuthenticated && !auth.isAdmin) {
      this.setUpUserList([ auth.user ]);
    } else {
      User.fetchList()
        .then((_users) => {
          this.setUpUserList(_users);
        })
        .catch((e) => {
          Alert.error(`${$._('actionfailure')}\n${e.message}`, e);
        });
    }
  }

  setUpUserList(_users) {
    this.model.userList = _users;
    if (_users.length) {
      let userId = _users[0].listValue;
      if (this.state.history) {
        userId = this.state.history.userId.toString();
      } else if (auth.isAuthenticated) {
        const user = this.model.userList.find((_user) => _user.listValue === auth.user.listValue);
        if (user) {
          userId = user.listValue;
        }
      }
      this.model.currentUserId = userId;
    }
  }

  /**
   * @param {State} state
   */
  setObservers(state) {
    this.onChanged('userList', (list) => {
      this.select.setOptions(list);
    });
    this.onChanged('currentUserId', (listValue) => {
      this.state.showAllUsers = listValue === Select.allValue;
      this.state.currentUser = this.model.userList.find((_user) => _user.listValue === listValue) || null;
      UserViewModel.setMenuVisible(this.editEl, this.state.currentUser !== null && !this.state.currentUser.isEqualTo(auth.user));
    });
    state.onChanged('showLatest', (showLatest) => {
      if (showLatest) {
        this.select.showAllOption();
      } else {
        this.select.hideAllOption();
      }
    });
    state.onChanged('history', (history) => {
      if (history && history.userId) {
        this.model.currentUserId = history.userId.toString();
      }
    });
  }

  showDialog(action) {
    const vm = new UserDialogModel(this, action);
    vm.init();
  }

  /**
   * @param {User} newUser
   */
  onUserAdded(newUser) {
    this.model.userList.push(newUser);
    this.model.userList.sort((a, b) => ((a.login > b.login) ? 1 : -1));
  }

  onUserDeleted() {
    let index = this.model.userList.indexOf(this.state.currentUser);
    this.state.currentUser = null;
    if (index !== -1) {
      this.model.userList.splice(index, 1);
      if (this.model.userList.length) {
        if (index >= this.model.userList.length) {
          index = this.model.userList.length - 1;
        }
        this.model.currentUserId = this.model.userList[index].listValue;
      } else {
        this.model.currentUserId = '0';
      }
    }
  }

  /**
   * @param {HTMLElement} el
   * @param {boolean} visible
   */
  static setMenuVisible(el, visible) {
    if (el) {
      if (visible) {
        el.classList.remove('menu-hidden');
      } else {
        el.classList.add('menu-hidden');
      }
    }
  }

}
