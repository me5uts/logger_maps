/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Observer from './Observer.js';

export default class Select {

  /**
   * @param {HTMLSelectElement} element Select element
   * @param {string=} head Optional header text
   * @param {string=} all Optional all option text
   */
  constructor(element, head, all) {
    if (!(element instanceof HTMLSelectElement)) {
      throw new Error('Invalid argument for select');
    }
    this.element = element;
    this.hasAllOption = false;
    this.allText = '';
    if (all && all.length) {
      this.allText = all;
    }
    if (head && head.length) {
      this.head = head;
    } else {
      this.hasHead = false;
      this.headText = '';
    }
  }

  /**
   * @param {string} value
   */
  set selected(value) {
    if (this.hasValue(value)) {
      this.element.value = value;
    }
  }

  get selected() {
    return this.element.value;
  }

  /**
   * @param {string} text
   */
  set head(text) {
    if (text.length) {
      this.hasHead = true;
      this.headText = text;
      this.addHead();
    }
  }

  /**
   * @return {string}
   */
  get head() {
    return this.headText;
  }

  /**
   * @param {string=} text Optional text
   */
  showAllOption(text) {
    if (text) {
      this.allText = text;
    }
    this.hasAllOption = true;
    const index = this.hasHead ? 1 : 0;
    this.element.add(new Option(this.allText, Select.allValue), index);
  }

  hideAllOption() {
    const isSelectedAll = this.selected === Select.allValue;
    this.hasAllOption = false;
    this.remove(Select.allValue);
    if (isSelectedAll) {
      this.selected = this.hasHead ? Select.headValue : '';
      this.element.dispatchEvent(new Event('change'));
    }
  }

  addHead() {
    const head = new Option(this.headText, Select.headValue, true, true);
    head.disabled = true;
    this.element.options.add(head, 0);
  }

  /**
   * @param {string} value
   * @return {boolean}
   */
  hasValue(value) {
    return (typeof this.getOption(value) !== 'undefined');
  }

  /**
   * @param {string} value
   */
  getOption(value) {
    return [ ...this.element.options ].find((o) => o.value === value);
  }

  /**
   * @param {string} value
   */
  remove(value) {
    /** @type HTMLOptionElement */
    const option = this.getOption(value);
    if (option) {
      this.element.remove(option.index);
    }
  }

  /**
   * @param {ListItem[]} options
   * @param {string=} selected
   */
  setOptions(options, selected) {
    selected = selected || this.element.value;
    this.element.options.length = 0;
    if (this.hasHead) {
      this.addHead();
    }
    if (this.hasAllOption) {
      this.element.add(new Option(this.allText, Select.allValue, false, selected === Select.allValue));
    }
    for (const option of options) {
      const optEl = new Option(option.listText, option.listValue, false, selected === option.listValue);
      this.element.add(optEl);
      Observer.observe(option, 'listText', (text) => {
        optEl.text = text;
      })
    }
  }

  static get allValue() {
    return 'all';
  }

  static get headValue() {
    return '0';
  }
}
