/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Observer from '../src/Observer.js';
import Utils from '../src/Utils.js';
import ViewModel from '../src/ViewModel.js';

describe('ViewModel tests', () => {

  let model;
  let vm;
  const propertyString = 'propertyString';
  const propertyStringVal = '1';
  const propertyBool = 'propertyBool';
  const propertyBoolVal = false;
  const propertyFunction = 'propertyFunction';

  beforeEach(() => {
    model = {};
    model[propertyString] = propertyStringVal;
    model[propertyBool] = propertyBoolVal;
    // eslint-disable-next-line no-empty-function
    model[propertyFunction] = () => {};
    vm = new ViewModel(model);
  });

  it('should create instance with model as parameter', () => {
    // when
    const viewModel = new ViewModel(model);
    // then
    expect(viewModel.model).toBe(model);
  });

  it('should call bind method with each model property as parameter', () => {
    // given
    spyOn(ViewModel.prototype, 'bind');
    // when
    vm.bindAll();
    // then
    expect(ViewModel.prototype.bind).toHaveBeenCalledTimes(3);
    expect(ViewModel.prototype.bind).toHaveBeenCalledWith(propertyString);
    expect(ViewModel.prototype.bind).toHaveBeenCalledWith(propertyBool);
  });

  it('should set root element', () => {
    // given
    spyOn(ViewModel.prototype, 'bind');
    const rootEl = document.querySelector('body');
    // when
    vm.bindAll(rootEl);
    // then
    expect(vm.root).toEqual(rootEl);
  });

  it('should set up binding between model property and DOM input element', () => {
    // given
    /** @type {HTMLInputElement} */
    const inputElement = Utils.nodeFromHtml(`<input type="text" value="${propertyStringVal}" data-bind="${propertyString}">`);
    document.body.appendChild(inputElement);
    // when
    vm.bind(propertyString);
    // then
    expect(Observer.isObserved(vm.model, propertyString)).toBe(true);
    expect(Observer.isObserved(vm.model, propertyBool)).toBe(false);
    expect(vm.model[propertyString]).toBe(propertyStringVal);
    expect(inputElement.value).toBe(propertyStringVal);
    // when
    inputElement.value = propertyStringVal + 1;
    inputElement.dispatchEvent(new Event('change'));
    // then
    expect(vm.model[propertyString]).toBe(propertyStringVal + 1);
    // when
    vm.model[propertyString] = propertyStringVal;
    // then
    expect(inputElement.value).toBe(propertyStringVal);
  });

  it('should set up binding between model property and DOM select element', () => {
    // given
    const html = `<select data-bind="${propertyString}">
      <option value=""></option>
      <option selected value="${propertyStringVal}"></option>
      </select>`;
    /** @type {HTMLInputElement} */
    const selectElement = Utils.nodeFromHtml(html);
    document.body.appendChild(selectElement);
    // when
    vm.bind(propertyString);
    // then
    expect(Observer.isObserved(vm.model, propertyString)).toBe(true);
    expect(Observer.isObserved(vm.model, propertyBool)).toBe(false);
    expect(vm.model[propertyString]).toBe(propertyStringVal);
    expect(selectElement.value).toBe(propertyStringVal);
    // when
    selectElement.value = '';
    selectElement.dispatchEvent(new Event('change'));
    // then
    expect(vm.model[propertyString]).toBe('');
    // when
    vm.model[propertyString] = propertyStringVal;
    // then
    expect(selectElement.value).toBe(propertyStringVal);
  });

  it('should set up binding between model property and DOM checkbox element', () => {
    // given
    /** @type {HTMLInputElement} */
    const checkboxElement = Utils.nodeFromHtml(`<input type="checkbox" data-bind="${propertyBool}">`);
    document.body.appendChild(checkboxElement);
    checkboxElement.checked = false;
    // when
    vm.bind(propertyBool);
    // then
    expect(Observer.isObserved(vm.model, propertyBool)).toBe(true);
    expect(Observer.isObserved(vm.model, propertyString)).toBe(false);
    expect(vm.model[propertyBool]).toBe(propertyBoolVal);
    expect(checkboxElement.checked).toBe(propertyBoolVal);
    // when
    const newValue = !propertyBoolVal;
    checkboxElement.checked = newValue;
    checkboxElement.dispatchEvent(new Event('change'));
    // then
    expect(vm.model[propertyBool]).toBe(newValue);
    // when
    vm.model[propertyBool] = !newValue;
    // then
    expect(checkboxElement.checked).toBe(!newValue);
  });

  it('should bind DOM anchor element click event to model property', () => {
    // given
    /** @type {HTMLAnchorElement} */
    const anchorElement = Utils.nodeFromHtml(`<a data-bind="${propertyFunction}">`);
    document.body.appendChild(anchorElement);
    spyOn(model, propertyFunction);
    // when
    vm.bind(propertyFunction);
    // then
    expect(Observer.isObserved(vm.model, propertyFunction)).toBe(false);
    expect(vm.model[propertyFunction]).toBeInstanceOf(Function);
    // when
    anchorElement.dispatchEvent(new Event('click'));
    // then
    expect(model[propertyFunction]).toHaveBeenCalledTimes(1);
    expect(model[propertyFunction]).toHaveBeenCalledWith(jasmine.any(Event));
    expect(model[propertyFunction].calls.mostRecent().args[0].target).toBe(anchorElement);
  });

  it('should bind DOM div element to model property', () => {
    // given
    /** @type {HTMLDivElement} */
    const divElement = Utils.nodeFromHtml(`<div data-bind="${propertyString}"></div>`);
    document.body.appendChild(divElement);
    const newContent = '<span>new value</span>';
    // when
    vm.bind(propertyString);
    // then
    expect(Observer.isObserved(vm.model, propertyString)).toBe(true);
    // when
    model[propertyString] = newContent;
    // then
    expect(divElement.innerHTML).toBe(newContent);
  });

  it('should start observing model property', () => {
    // given
    // eslint-disable-next-line no-empty-function
    const callback = () => {};
    spyOn(Observer, 'observe');
    // when
    vm.onChanged(propertyString, callback);
    // then
    expect(Observer.observe).toHaveBeenCalledTimes(1);
    expect(Observer.observe).toHaveBeenCalledWith(vm.model, propertyString, callback);
  });

  it('should stop observing model property', () => {
    // given
    // eslint-disable-next-line no-empty-function
    const callback = () => {};
    spyOn(Observer, 'unobserve');
    // when
    vm.unsubscribe(propertyString, callback);
    // then
    expect(Observer.unobserve).toHaveBeenCalledTimes(1);
    expect(Observer.unobserve).toHaveBeenCalledWith(vm.model, propertyString, callback);
  });

  it('should get bound element by property name', () => {
    // given
    const property = 'property';
    spyOn(vm.root, 'querySelector');
    // when
    vm.getBoundElement(property);
    // then
    expect(vm.root.querySelector).toHaveBeenCalledWith(`[data-bind='${property}']`);
  });

});
