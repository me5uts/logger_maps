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

import { auth, config, lang } from '../src/Initializer.js';
import MainViewModel from '../src/models/MainViewModel.js';
import Observer from '../src/Observer.js';
import State from '../src/State.js';
import User from '../src/User.js';
import ViewModel from '../src/ViewModel.js';

describe('MainViewModel tests', () => {

  const hiddenClass = 'menu-hidden';
  let vm;
  let state;

  beforeEach(() => {
    config.reinitialize();
    lang.init(config);
    spyOn(lang, '_').and.callFake((arg) => arg);
    spyOn(window, 'addEventListener');
    spyOn(window, 'removeEventListener').and.callThrough();
    state = new State();
    vm = new MainViewModel(state);
  });

  afterEach(() => {
    Observer.unobserveAll(lang);
    document.body.innerHTML = '';
  });

  it('should create instance', () => {
    expect(vm).toBeInstanceOf(ViewModel);
    expect(vm.state).toBe(state);
  });

  it('should initialize html', () => {
    // given
    vm.init();
    const menuEl = document.querySelector('#menu');
    const userMenuEl = document.querySelector('#user-menu');
    // tnen
    expect(vm.menuEl).toBe(menuEl);
    expect(vm.userMenuEl).toBe(userMenuEl);
  });

  it('should hide side menu', (done) => {
    // given
    vm.init();
    const menuEl = document.querySelector('#menu');
    const menuButtonEl = document.querySelector('#menu-button a');
    // when
    menuButtonEl.click();
    // then
    setTimeout(() => {
      expect(menuEl.classList.contains(hiddenClass)).toBe(true);
      done();
    }, 100);
  });

  it('should show side menu', (done) => {
    // given
    vm.init();
    const menuEl = document.querySelector('#menu');
    const menuButtonEl = document.querySelector('#menu-button a');
    menuEl.classList.add(hiddenClass);
    // when
    menuButtonEl.click();
    // then
    setTimeout(() => {
      expect(menuEl.classList.contains(hiddenClass)).toBe(false);
      done();
    }, 100);
  });

  it('should hide user menu', (done) => {
    // given
    auth.user = new User(1, 'test', false);
    vm.init();
    const userMenuEl = document.querySelector('#user-menu');
    const userButtonEl = document.querySelector('a[data-bind="onShowUserMenu"]');
    userMenuEl.classList.remove(hiddenClass);
    // when
    userButtonEl.click();
    // then
    setTimeout(() => {
      expect(userMenuEl.classList.contains(hiddenClass)).toBe(true);
      done();
    }, 100);
  });

  it('should show user menu', (done) => {
    // given
    auth.user = new User(1, 'test', false);
    vm.init();
    const userMenuEl = document.querySelector('#user-menu');
    const userButtonEl = document.querySelector('a[data-bind="onShowUserMenu"]');
    // when
    userButtonEl.click();
    // then
    setTimeout(() => {
      expect(userMenuEl.classList.contains(hiddenClass)).toBe(false);
      expect(window.addEventListener).toHaveBeenCalledTimes(1);
      expect(window.addEventListener).toHaveBeenCalledWith('click', vm.hideUserMenuCallback, true);
      done();
    }, 100);
  });

  it('should hide user menu on window click', (done) => {
    // given
    window.addEventListener.and.callThrough();
    window.addEventListener('click', vm.hideUserMenuCallback, true);
    auth.user = new User(1, 'test', false);
    vm.init();
    const userMenuEl = document.querySelector('#user-menu');
    userMenuEl.classList.remove(hiddenClass);

    // when
    document.body.click();
    // then
    setTimeout(() => {
      expect(userMenuEl.classList.contains(hiddenClass)).toBe(true);
      expect(window.removeEventListener).toHaveBeenCalledTimes(1);
      expect(window.removeEventListener).toHaveBeenCalledWith('click', vm.hideUserMenuCallback, true);
      done();
    }, 100);
  });

});
