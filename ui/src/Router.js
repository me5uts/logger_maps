/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */


import { auth, config } from './Initializer.js';
import ChartViewModel from './models/ChartViewModel.js';
import ConfigViewModel from './models/ConfigViewModel.js';
import LoginViewModel from './models/LoginViewModel.js';
import MainViewModel from './models/MainViewModel.js';
import MapViewModel from './models/MapViewModel.js';
import Permalink from './Permalink.js';
import Spinner from './Spinner.js';
import State from './State.js';
import TrackViewModel from './models/TrackViewModel.js';
import UserViewModel from './models/UserViewModel.js';

/**
 * @class Router
 */
export default class Router {

  // eslint-disable-next-line max-params
  static loadMainView(state, permalink, linkState, spinner) {
    const mainVM = new MainViewModel(state);
    mainVM.init();
    const userVM = new UserViewModel(state);
    const trackVM = new TrackViewModel(state);
    const mapVM = new MapViewModel(state);
    const chartVM = new ChartViewModel(state);
    const configVM = new ConfigViewModel(state);
    permalink.init().onPop(linkState);
    spinner.init();
    userVM.init();
    trackVM.init();
    mapVM.init().loadMapAPI(config.mapApi);
    chartVM.init();
    configVM.init();
    userVM.start();

    mapVM.onChanged('markerOver', (id) => {
      if (id !== null) {
        chartVM.onPointOver(id);
      } else {
        chartVM.onPointOut();
      }
    });
    mapVM.onChanged('markerSelect', (id) => {
      if (id !== null) {
        chartVM.onPointSelect(id);
      } else {
        chartVM.onPointUnselect();
      }
    });
    chartVM.onChanged('pointSelected', (id) => {
      if (id !== null) {
        mapVM.api.animateMarker(id);
      }
    });
  }

  static loadLoginView(state) {
    const loginVM = new LoginViewModel(state);
    loginVM.init();
  }

  static initView() {
    const state = new State();
    const permalink = new Permalink(state);
    const spinner = new Spinner(state);
    if (config.requireAuth && !auth.isAuthenticated) {
      // show login
      Router.loadLoginView(state);
    } else {
      Permalink.parseHash().then((linkState) => {
        Router.loadMainView(state, permalink, linkState, spinner);
      })
    }
  }
}
