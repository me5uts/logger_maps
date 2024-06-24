/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { lang as $ } from './Initializer.js';

export default class Dialog {

  /** @var {HTMLDivElement} */
  element;
  /** @var {boolean} */
  visible = false;

  /**
   * Builds modal dialog
   * @param {(string|Node|NodeList|Array.<Node>)} content
   */
  constructor(content) {
    this.element = Dialog.#getDialog(content);
  }

  /**
   * Get dialog HTML element
   * @param content
   * @return {HTMLDivElement}
   */
  static #getDialog(content) {
    const dialog = document.createElement('div');
    dialog.setAttribute('id', 'modal');
    const dialogHeader = document.createElement('div');
    dialogHeader.setAttribute('id', 'modal-header');
    const buttonClose = document.createElement('button');
    buttonClose.setAttribute('id', 'modal-close');
    buttonClose.setAttribute('type', 'button');
    buttonClose.setAttribute('class', 'button-reject');
    buttonClose.setAttribute('data-bind', 'onCancel');
    const img = document.createElement('img');
    img.setAttribute('src', 'images/close.svg');
    img.setAttribute('alt', $._('close'));
    buttonClose.append(img);
    dialogHeader.append(buttonClose);
    const dialogBody = document.createElement('div');
    dialogBody.setAttribute('id', 'modal-body');
    if (typeof content === 'string') {
      dialogBody.innerHTML = content;
    } else if (content instanceof NodeList || content instanceof Array) {
      for (const node of content) {
        dialogBody.append(node);
      }
    } else {
      dialogBody.append(content);
    }
    dialogBody.prepend(dialogHeader);
    dialog.append(dialogBody);
    return dialog;
  }

  /**
   * Show modal dialog
   */
  show() {
    if (!this.visible) {
      document.body.append(this.element);
      this.visible = true;
      this.autofocus();
    }
  }

  /**
   * Set focus to element with autofocus attribute
   */
  autofocus() {
    const focusEl = this.element.querySelector('[autofocus]');
    if (focusEl) {
      focusEl.focus();
    }
  }

  /**
   * Remove modal dialog
   */
  destroy() {
    document.body.removeChild(this.element);
    this.visible = false
  }

  /**
   * Show confirmation dialog and return user decision
   * @param {string} message
   * @return {boolean} True if confirmed, false otherwise
   */
  static isConfirmed(message) {
    return confirm(message);
  }
}
