/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { lang as $ } from '../Initializer.js';
import Alert from '../Alert.js';
import Dialog from '../Dialog.js';
import Utils from '../Utils.js';
import ViewModel from '../ViewModel.js';

export default class TrackDialogModel extends ViewModel {

  /**
   * @param {TrackViewModel} viewModel
   */
  constructor(viewModel) {
    super({
      onTrackDelete: null,
      onTrackUpdate: null,
      onCancel: null,
      trackname: ''
    });
    this.track = viewModel.state.currentTrack;
    this.trackVM = viewModel;
    this.model.onTrackDelete = () => this.onTrackDelete();
    this.model.onTrackUpdate = () => this.onTrackUpdate();
    this.model.onCancel = () => this.onCancel();
  }

  init() {
    const html = this.getHtml();
    this.dialog = new Dialog(html);
    this.dialog.show();
    this.bindAll(this.dialog.element);
  }

  /**
   * @return {string}
   */
  getHtml() {
    return `<div class="red-button button-resolve"><b><a data-bind="onTrackDelete">${$._('deltrack')}</a></b></div>
      <div>${$._('editingtrack', `<b>${Utils.htmlEncode(this.track.name)}</b>`)}</div>
      <div style="clear: both; padding-bottom: 1em;"></div>
      <form id="trackForm">
        <label><b>${$._('trackname')}</b></label>
        <input type="text" placeholder="${$._('trackname')}" name="trackname" data-bind="trackname" value="${Utils.htmlEncode(this.track.name)}" required autofocus>
        <div class="buttons">
          <button class="button-reject" data-bind="onCancel" type="button">${$._('cancel')}</button>
          <button class="button-resolve" data-bind="onTrackUpdate" type="submit">${$._('submit')}</button>
        </div>
      </form>`;
  }

  onTrackDelete() {
    if (Dialog.isConfirmed($._('trackdelwarn', Utils.htmlEncode(this.track.name)))) {
      this.track.delete().then(() => {
        this.trackVM.onTrackDeleted();
        this.dialog.destroy();
      }).catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
    }
  }

  onTrackUpdate() {
    if (this.validate()) {
      this.track.setName(this.model.trackname);
      this.track.saveMeta()
        .then(() => this.dialog.destroy())
        .catch((e) => { Alert.error(`${$._('actionfailure')}\n${e.message}`, e); });
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
    if (this.model.trackname === this.track.name) {
      return false;
    }
    if (!this.model.trackname) {
      Alert.error($._('allrequired'));
      return false;
    }
    return true;
  }
}
