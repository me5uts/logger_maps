/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Alert from '../src/Alert.js';

describe('Alert tests', () => {

  const message = 'test message';
  let alert;

  beforeEach(() => {
    spyOn(window, 'requestAnimationFrame').and.callFake((callback) => callback());
  })

  afterEach(() => {
    if (alert) {
      alert.destroy();
    }
    document.body.innerText = '';
  });

  it('should create alert box with message', () => {
    // when
    alert = new Alert(message);
    const textEl = alert.box.firstChild;
    // then
    expect(textEl.innerText).toBe(message);
  });

  it('should create alert box with autoClose option', () => {
    // given
    const autoClose = 1;
    const options = { autoClose }
    // when
    alert = new Alert(message, options);
    const textEl = alert.box.firstChild;
    // then
    expect(textEl.innerText).toBe(message);
    expect(alert.autoClose).toBe(autoClose);
  });

  it('should create alert box with id option', () => {
    // given
    const id = 'testId';
    const options = { id }
    // when
    alert = new Alert(message, options);
    const boxEl = alert.box;
    const textEl = alert.box.firstChild;
    // then
    expect(textEl.innerText).toBe(message);
    expect(boxEl.id).toBe(id);
  });

  it('should create alert box with class option', () => {
    // given
    const className = 'test_class';
    const options = { class: className }
    // when
    alert = new Alert(message, options);
    const boxEl = alert.box;
    const textEl = alert.box.firstChild;
    // then
    expect(textEl.innerText).toBe(message);
    expect(boxEl.classList).toContain(className);
  });

  it('should render and destroy alert box', () => {
    // given
    spyOn(window, 'setTimeout').and.callFake((callback) => callback());
    const id = 'testId';
    const options = { id }
    alert = new Alert(message, options);

    // when
    alert.render();
    // then
    expect(document.querySelector(`#${id}`)).not.toBeNull();

    // when
    alert.destroy();
    // then
    expect(document.querySelector(`#${id}`)).toBeNull();
  });

  it('should show and autoclose alert box', (done) => {
    // given
    jasmine.clock().install();
    const id = 'testId';
    const options = { id: id, autoClose: 50 }

    // when
    alert = Alert.show(message, options);
    // then
    expect(document.querySelector(`#${id}`)).not.toBeNull();
    jasmine.clock().tick(5000);
    jasmine.clock().uninstall();
    setTimeout(() => {
      expect(document.querySelector(`#${id}`)).toBeNull();
      done();
    }, 100);
  });

  it('should close alert box on close button click', (done) => {
    // given
    jasmine.clock().install();
    const id = 'testId';
    const options = { id }
    alert = Alert.show(message, options);
    const closeButton = alert.box.querySelector('button');
    // when
    closeButton.click();
    jasmine.clock().tick(5000);
    jasmine.clock().uninstall();
    // then
    setTimeout(() => {
      expect(document.querySelector(`#${id}`)).toBeNull();
      done();
    }, 100);
  });

  it('should show error alert box', () => {
    // when
    alert = Alert.error(message);
    // then
    expect(document.querySelector('.alert.error')).not.toBeNull();
  });

  it('should show toast alert box', () => {
    // when
    alert = Alert.toast(message);
    // then
    expect(document.querySelector('.alert.toast')).not.toBeNull();
    expect(alert.autoClose).toBeGreaterThan(0);
  });

});
