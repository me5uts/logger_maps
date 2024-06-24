/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
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
