/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Observer from './Observer.js';

/**
 * @class
 * @property {?Track} currentTrack
 * @property {?User} currentUser
 * @property {boolean} showLatest
 * @property {boolean} showAllUsers
 * @property {number} activeJobs
 * @property {?MapParams} mapParams
 * @property {?PermalinkState} history
 */
export default class State {

  constructor() {
    this.currentTrack = null;
    this.currentUser = null;
    this.showLatest = false;
    this.showAllUsers = false;
    this.activeJobs = 0;
    this.mapParams = null;
    this.history = null;
  }

  jobStart() {
    this.activeJobs++;
  }

  jobStop() {
    this.activeJobs--;
  }

  /**
   * @param {string} property
   * @param {ObserveCallback} callback
   */
  onChanged(property, callback) {
    Observer.observe(this, property, callback);
  }
}
