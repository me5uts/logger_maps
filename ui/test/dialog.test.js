/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { config, lang } from '../src/Initializer.js';
import Dialog from '../src/Dialog.js';
import Observer from '../src/Observer.js';

describe('Dialog tests', () => {

  let content;
  let dialog;

  beforeEach(() => {
    config.reinitialize();
    lang.init(config);
    spyOn(lang, '_').and.returnValue('{placeholder}');
    content = 'Test content';
    dialog = new Dialog(content);
  });

  afterEach(() => {
    document.body.innerHTML = '';
    Observer.unobserveAll(lang);
  });

  it('should create dialog with string content', () => {
    // when
    const body = dialog.element.querySelector('#modal-body');
    body.firstChild.remove();
    // then
    expect(body.innerHTML).toBe(content);
    expect(dialog.visible).toBe(false);
  });

  it('should create dialog with node content', () => {
    // given
    content = document.createElement('div');
    dialog = new Dialog(content);
    // when
    const body = dialog.element.querySelector('#modal-body');
    body.firstChild.remove();
    // then
    expect(body.firstChild).toBe(content);
  });

  it('should create dialog with node array content', () => {
    // given
    content = [
      document.createElement('div'),
      document.createElement('div')
    ];
    dialog = new Dialog(content);
    // when
    const body = dialog.element.querySelector('#modal-body');
    body.firstChild.remove();
    // then
    expect(body.children[0]).toBe(content[0]);
    expect(body.children[1]).toBe(content[1]);
  });

  it('should create dialog with node list content', () => {
    // given
    const div1 = document.createElement('div');
    const div2 = document.createElement('div');
    const el = document.createElement('div');
    el.append(div1, div2);
    content = el.childNodes;
    dialog = new Dialog(content);
    // when
    const body = dialog.element.querySelector('#modal-body');
    body.firstChild.remove();
    // then
    expect(body.childNodes).toEqual(content);
  });

  it('should show dialog', () => {
    // when
    dialog.show();
    // then
    expect(document.querySelector('#modal')).toBe(dialog.element);
    expect(dialog.visible).toBe(true);
  });

  it('should destroy dialog', () => {
    // given
    dialog.show();
    // when
    dialog.destroy();
    // then
    expect(document.querySelector('#modal')).toBe(null);
    expect(dialog.visible).toBe(false);
  });

  it('should show confirm dialog', () => {
    // given
    const message = 'confirm message';
    spyOn(window, 'confirm');
    // when
    Dialog.isConfirmed(message);
    // then
    expect(window.confirm).toHaveBeenCalledWith(message);
  });

});
