/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Http from '../src/Http.js';

describe('Ajax tests', () => {

  const url = 'http://ulogger.test/';
  const validResponse = { id: 1 };
  const invalidResponse = 'invalid';
  const errorResponse = { error: true, message: 'response error' };
  const form = document.createElement('form');
  const input = document.createElement('input');
  input.type = 'text';
  input.name = 'p1';
  input.value = 'test';
  form.appendChild(input);

  beforeEach(() => {
    spyOn(XMLHttpRequest.prototype, 'open').and.callThrough();
    spyOn(XMLHttpRequest.prototype, 'setRequestHeader').and.callThrough();
    spyOn(XMLHttpRequest.prototype, 'send');
    spyOnProperty(XMLHttpRequest.prototype, 'readyState').and.returnValue(XMLHttpRequest.DONE);
  });

  it('should make POST request', () => {
    // when
    Http.post(url).catch(() => { /* ignore */ });
    // then
    expect(XMLHttpRequest.prototype.setRequestHeader).toHaveBeenCalledWith('Content-type', 'application/x-www-form-urlencoded');
    expect(XMLHttpRequest.prototype.open).toHaveBeenCalledWith('POST', url, true);
  });

  it('should make GET request', () => {
    // when
    Http.get(url).catch(() => { /* ignore */ });
    // then
    expect(XMLHttpRequest.prototype.setRequestHeader).not.toHaveBeenCalled();
    expect(XMLHttpRequest.prototype.open).toHaveBeenCalledWith('GET', url, true);
  });

  it('should make GET request with parameters', () => {
    // when
    Http.get(url, { p1: 1, p2: 'test' }).catch(() => { /* ignore */ });
    // then
    expect(XMLHttpRequest.prototype.open).toHaveBeenCalledWith('GET', `${url}?p1=1&p2=test`, true);
    expect(XMLHttpRequest.prototype.send).toHaveBeenCalledWith(null);
  });

  it('should make POST request with parameters', () => {
    // when
    Http.post(url, { p1: 1, p2: 'test' }).catch(() => { /* ignore */ });
    // then
    expect(XMLHttpRequest.prototype.open).toHaveBeenCalledWith('POST', url, true);
    expect(XMLHttpRequest.prototype.send).toHaveBeenCalledWith('p1=1&p2=test');
  });

  it('should make POST request with form data', () => {
    // when
    Http.post(url, form).catch(() => { /* ignore */ });
    // then
    expect(XMLHttpRequest.prototype.setRequestHeader).not.toHaveBeenCalled();
    expect(XMLHttpRequest.prototype.open).toHaveBeenCalledWith('POST', url, true);
    expect(XMLHttpRequest.prototype.send).toHaveBeenCalledWith(new FormData(form));
  });

  it('should make GET request with form data', () => {
    // when
    Http.get(url, form).catch(() => { /* ignore */ });
    // then
    expect(XMLHttpRequest.prototype.setRequestHeader).not.toHaveBeenCalled();
    expect(XMLHttpRequest.prototype.open).toHaveBeenCalledWith('GET', `${url}?p1=test`, true);
    expect(XMLHttpRequest.prototype.send).toHaveBeenCalledWith(null);
  });

  it('should make successful request and return value', (done) => {
    // when
    spyOnProperty(XMLHttpRequest.prototype, 'status').and.returnValue(200);
    spyOnProperty(XMLHttpRequest.prototype, 'responseText').and.returnValue(JSON.stringify(validResponse));
    // then
    Http.get(url)
      .then((result) => {
      expect(result).toEqual(validResponse);
      done();
    })
      .catch((e) => done.fail(`reject callback called (${e})`));
  });

  it('should make successful request and return error with message', (done) => {
    // when
    spyOnProperty(XMLHttpRequest.prototype, 'status').and.returnValue(200);
    spyOnProperty(XMLHttpRequest.prototype, 'responseText').and.returnValue(JSON.stringify(errorResponse));
    // then
    Http.get(url)
      .then(() => done.fail('resolve callback called'))
      .catch((e) => {
        expect(e.message).toBe(errorResponse.message);
        done();
      });
  });

  it('should make successful request and return error without message', (done) => {
    // when
    spyOnProperty(XMLHttpRequest.prototype, 'status').and.returnValue(200);
    spyOnProperty(XMLHttpRequest.prototype, 'responseText').and.returnValue(JSON.stringify({ error: true }));
    // then
    Http.get(url)
      .then(() => done.fail('resolve callback called'))
      .catch((e) => {
        expect(e.message).toBe('');
        done();
      });
  });

  it('should make request and fail with HTTP error code', (done) => {
    // when
    const status = 401;
    spyOnProperty(XMLHttpRequest.prototype, 'status').and.returnValue(status);
    // then
    Http.get(url)
      .then(() => done.fail('resolve callback called'))
      .catch((e) => {
        expect(e.message).toBe(`HTTP error ${status}`);
        done();
      });
  });

  it('should make request and fail with JSON parse error', (done) => {
    // when
    spyOnProperty(XMLHttpRequest.prototype, 'status').and.returnValue(200);
    spyOnProperty(XMLHttpRequest.prototype, 'responseText').and.returnValue(invalidResponse);
    // then
    Http.get(url)
      .then(() => done.fail('resolve callback called'))
      .catch((e) => {
        expect(e.message).toContain('JSON');
        done();
      });
  });

});
