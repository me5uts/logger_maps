/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */


import Alert from './Alert.js';

export default class Spinner {

  constructor(state) {
    this.spinner = null;
    this.state = state;
  }

  init() {
    this.state.onChanged('activeJobs', (jobs) => {
      if (jobs > 0) {
        if (!this.spinner) {
          this.spinner = Alert.spinner();
        }
      } else if (this.spinner) {
        this.spinner.destroy();
        this.spinner = null;
      }
    });
  }
}
