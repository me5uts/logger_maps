/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import ListItem from './ListItem.js';

export default class Layer extends ListItem {

  /**
   * @param {number} id
   * @param {string} name
   * @param {string} url
   * @param {number} priority
   */
  // eslint-disable-next-line max-params
  constructor(id, name, url, priority) {
    super();
    this.id = id;
    this.name = name;
    this.url = url;
    this.priority = priority;
    this.listItem(id, name);
  }

  /**
   * @param {string} name
   */
  setName(name) {
    this.name = name;
    this.listItem(this.id, this.name);
  }

  /**
   * @param {string} url
   */
  setUrl(url) {
    this.url = url;
  }

}
