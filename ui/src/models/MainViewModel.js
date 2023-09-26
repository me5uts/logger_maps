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
import Session from '../Session.js';
import ViewModel from '../ViewModel.js';

const hiddenClass = 'menu-hidden';

export default class MainViewModel extends ViewModel {

  /**
   * @param {State} state
   */
  constructor(state) {
    super({
      onMenuToggle: null,
      onShowUserMenu: null,
      onLogin: null,
      onLogout: null
    });
    this.state = state;
    this.model.onMenuToggle = () => this.toggleSideMenu();
    this.model.onShowUserMenu = () => this.toggleUserMenu();
    this.model.onLogin = () => this.login();
    this.model.onLogout = () => this.logout();
    this.hideUserMenuCallback = (e) => this.hideUserMenu(e);
  }

  /**
   * @return {MainViewModel}
   */
  init() {
    this.loadHtmlIntoBody();
    this.menuEl = document.querySelector('#menu');
    this.userMenuEl = document.querySelector('#user-menu');
    this.bindAll();
    return this;
  }

  toggleSideMenu() {
    if (this.menuEl.classList.contains(hiddenClass)) {
      this.menuEl.classList.remove(hiddenClass);
    } else {
      this.menuEl.classList.add(hiddenClass);
    }
  }

  /**
   * Toggle user menu visibility
   */
  toggleUserMenu() {
    if (this.userMenuEl.classList.contains(hiddenClass)) {
      this.userMenuEl.classList.remove(hiddenClass);
      window.addEventListener('click', this.hideUserMenuCallback, true);
    } else {
      this.userMenuEl.classList.add(hiddenClass);
    }
  }

  /**
   * Click listener callback to hide user menu
   * @param {MouseEvent} event
   */
  hideUserMenu(event) {
    const el = event.target;
    this.userMenuEl.classList.add(hiddenClass);
    window.removeEventListener('click', this.hideUserMenuCallback, true);
    if (el.parentElement.id !== 'user-menu') {
      event.stopPropagation();
    }
  }

  login() {
    Router.loadLoginView(this.state);
  }

  logout() {
    // let url = 'utils/logout.php';
    // if (!config.requireAuth) {
    //   url += `?hash=${window.location.hash.replace('#', '')}`;
    // }
    // Utils.openUrl(url);
    const session = new Session();
    session.logout().then(
      () => {
        console.log('successful logout')
        Router.initView()
      }
    ).catch(
      (e) => console.error('logout failed', e)
    );
  }

  getHtml() {
    let userMenu = '';
    let authMenu = '';
    if (auth.isAuthenticated) {
      let adminOptions = '';
      if (auth.isAdmin) {
        adminOptions = `
        <a id="editconfig" class="menu-link" data-bind="onConfigEdit">${$._('config')}</a>
        <a id="adduser" class="menu-link" data-bind="onUserAdd">${$._('adduser')}</a>
        <a id="edituser" class="menu-link" data-bind="onUserEdit">${$._('edituser')}</a>`;
      }
      userMenu = `
      <div>
        <a data-bind="onShowUserMenu"><img class="icon" alt="${$._('user')}" src="../../images/user.svg"> ${auth.user.login}</a>
        <div id="user-menu" class="menu-hidden">
          <a id="user-pass" data-bind="onPasswordChange"><img class="icon" alt="${$._('changepass')}" src="../../images/lock.svg"> ${$._('changepass')}</a>
          <a class="menu-link" data-bind="onLogout"><img class="icon" alt="${$._('logout')}" src="../../images/poweroff.svg"> ${$._('logout')}</a>
        </div>
      </div>`;
      authMenu = `
      <div class="section">
        <div id="import" class="menu-title">${$._('import')}</div>
        <form id="import-form" enctype="multipart/form-data" method="post">
          <input type="hidden" name="MAX_FILE_SIZE" value="${config.uploadMaxSize}" />
          <input type="file" id="input-file" name="gpx" data-bind="inputFile"/>
        </form>
        <a id="import-gpx" class="menu-link" data-bind="onImportGpx">gpx</a>
      </div>

      <div id="admin-menu">
        <div class="menu-title">${$._('adminmenu')}</div>
        ${adminOptions}
        <a id="edittrack" class="menu-link menu-hidden" data-bind="onTrackEdit">${$._('edittrack')}</a>
      </div>`;
    } else {
      userMenu = `
      <a class="menu-link" data-bind="onLogin"><img class="icon" alt="${$._('login')}" src="../../images/key.svg"> ${$._('login')}</a>
      `;
    }

    let langOptions = '';
    for (const [ langCode, langName ] of Object.entries($.getLangList())) {
      langOptions += `<option value="${langCode}"${config.lang === langCode ? ' selected' : ''}>${langName}</option>`;
    }

    let unitOptions = '';
    for (const units of [ 'metric', 'imperial', 'nautical' ]) {
      unitOptions += `<option value="${units}"${config.units === units ? ' selected' : ''}>${$._(units)}</option>`;
    }

    return `
<div id="container">
  <div id="menu">
    <div id="menu-content">
    
      ${userMenu}

      <div class="section">
        <label for="user">${$._('user')}</label>
        <select id="user" data-bind="currentUserId" name="user"></select>
      </div>

      <div class="section">
        <label for="track">${$._('track')}</label>
        <select id="track" data-bind="currentTrackId" name="track"></select>
        <input id="latest" type="checkbox" data-bind="showLatest"> <label for="latest">${$._('latest')}</label><br>
        <input id="auto-reload" type="checkbox" data-bind="autoReload"> <label for="auto-reload">${$._('autoreload')}</label> (<a id="set-interval" data-bind="onSetInterval"><span id="interval" data-bind="interval">${config.interval}</span></a> s)<br>
        <a id="force-reload" data-bind="onReload"> ${$._('reload')}</a><br>
      </div>

      <div id="summary" class="section" data-bind="summary"></div>

      <div class="section" data-bind="trackColor">
        <div class="menu-title">${$._('trackcolor')}</div>
        <input id="color-speed" type="checkbox" data-bind="speedVisible"> <label for="color-speed">${$._('speed')}</label><br>
        <input id="color-altitude" type="checkbox" data-bind="altitudeVisible"> <label for="color-altitude">${$._('altitude')}</label><br>
      </div>

      <div id="other" class="section">
        <a id="altitudes" class="menu-link menu-hidden" data-bind="onChartToggle">${$._('chart')}</a>
      </div>

      <div>
        <label for="api">${$._('api')}</label>
        <select id="api" name="api" data-bind="mapApi">
          <option value="gmaps"${(config.mapApi === 'gmaps') ? ' selected' : ''}>Google Maps</option>
          <option value="openlayers"${(config.mapApi === 'openlayers') ? ' selected' : ''}>OpenLayers</option>
        </select>
      </div>

      <div>
        <label for="lang">${$._('language')}</label>
        <select id="lang" name="lang" data-bind="lang">
          ${langOptions}
        </select>
      </div>

      <div class="section">
        <label for="units">${$._('units')}</label>
        <select id="units" name="units" data-bind="units">
          ${unitOptions}
        </select>
      </div>

      <div class="section">
        <div class="menu-title">${$._('export')}</div>
        <a id="export-kml" class="menu-link" data-bind="onExportKml">kml</a>
        <a id="export-gpx" class="menu-link" data-bind="onExportGpx">gpx</a>
      </div>

      ${authMenu}

    </div>
    <div id="menu-button"><a data-bind="onMenuToggle"></a></div>
    <div id="footer"><a target="_blank" href="https://github.com/bfabiszewski/ulogger-server"><span class="mi">μ</span>logger</a> ${config.version}</div>
  </div>

  <div id="main">
    <div id="map-canvas"></div>
    <div id="bottom">
      <div id="chart"></div>
      <a id="chart-close" data-bind="onChartToggle"><img src="../../images/close_blue.svg" alt="${$._('close')}"></a>
    </div>
  </div>

</div>`;
  }

}
