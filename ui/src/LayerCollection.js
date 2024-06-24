/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Layer from './Layer.js';

/**
 * @extends {Array.<Layer>}
 */
export default class LayerCollection extends Array {

  /**
   * Create new layer in layers array
   * @param {string} name
   * @param {string} url
   * @param {number} priority
   */
  // eslint-disable-next-line max-params
  addNewLayer(name, url, priority = 0) {
    this.addLayer(this.getMaxId() + 1, name, url, priority);
  }

  /**
   * @param {number} id
   * @param {string} name
   * @param {string} url
   * @param {number} priority
   */
  // eslint-disable-next-line max-params
  addLayer(id, name, url, priority = 0) {
    this.push(new Layer(id, name, url, priority));
  }

  /**
   * @param {number} id
   */
  delete(id) {
    const index = this.map((o) => o.id).indexOf(id);
    this.splice(index, 1);
  }

  /**
   * @param {number} id
   * @return {Layer}
   */
  get(id) {
    return this.find((o) => o.id === id);
  }

  /**
   * Return max id from layers array
   * @return {number}
   */
  getMaxId() {
    return Math.max(...this.map((o) => o.id), 0);
  }

  /**
   * Set layer with given id as priority
   * @param {number} id
   */
  setPriorityLayer(id) {
    for (const layer of this) {
      if (layer.id > 0 && layer.id === id) {
        layer.priority = 1;
      } else {
        layer.priority = 0;
      }
    }
  }

  /**
   * Return id of first layer with priority
   * @return {number}
   */
  getPriorityLayer() {
    for (const layer of this) {
      if (layer.priority > 0) {
        return layer.id;
      }
    }
    return 0;
  }

  /**
   * Load from array
   * @param {Array} layers
   */
  load(layers) {
    this.length = 0;
    for (const layer of layers) {
      if (layer.id > 0) {
        this.addLayer(layer.id, layer.name, layer.url, layer.priority);
      }
    }
  }

}

