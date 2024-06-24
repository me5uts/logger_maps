/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Select from './Select.js';

/**
 * @class ListItem
 * @property {string} listValue
 * @property {string} listText
 */
export default class ListItem {

  constructor() {
    this.listValue = Select.allValue;
    this.listText = '-';
  }

  /**
   * @param {string|number} id
   * @param {string|number} value
   */
  listItem(id, value) {
    this.listValue = String(id);
    this.listText = String(value);
  }

  /**
   * @return {string}
   */
  toString() {
    return `[${this.listValue}, ${this.listText}]`;
  }
}
