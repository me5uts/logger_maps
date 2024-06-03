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

import './assets/css/fonts.css';
import './assets/css/main.css';
import { lang as $, Initializer, initializer } from './Initializer.js';
import Alert from './Alert.js';
import Router from './Router.js';

const domReady = Initializer.waitForDom();
const initReady = initializer.initialize();

Promise.all([ domReady, initReady ])
  .then(() => {
    Router.initView();
  })
  .catch((msg) => {
    let title;
    try {
      title = $._('actionfailure');
    } catch {
      title = 'Initialization error'
    }
    return Alert.error(`${title}\n${msg}`);
  });
