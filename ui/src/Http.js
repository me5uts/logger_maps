/*
 * μlogger
 *
 * Copyright(C) 2019 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

export default class Http {

  /**
   * Perform POST HTTP request
   * @alias request
   */
  static post(url, data, options) {
    const params = options || {};
    params.method = 'POST';
    return this.request(url, data, params);
  }

  /**
   * Perform GET HTTP request
   * @alias request
   */
  static get(url, data, options) {
    const params = options || {};
    params.method = 'GET';
    return this.request(url, data, params);
  }

  /**
   * Perform HTTP request
   * @param {string} url Request URL
   * @param {Object|HTMLFormElement|FormData} [data] Optional request parameters: key/value pairs or form element
   * @param {Object} [options] Optional options
   * @param {string} [options.method='GET'] Optional query method, default 'GET'
   * @return {Promise<Object, Error>}
   */
  static request(url, data, options) {
    const params = [];
    data = data || {};
    options = options || {};
    const method = options.method || 'GET';
    const xhr = new XMLHttpRequest();
    return new Promise((resolve, reject) => {
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== XMLHttpRequest.DONE) { return; }
        let message = '';
        let error = true;
        if (xhr.status === 200) {
          try {
            const obj = JSON.parse(xhr.responseText);
            if (obj) {
              if (!obj.error) {
                if (resolve && typeof resolve === 'function') {
                  resolve(obj);
                }
                error = false;
              } else if (obj.message) {
                  message = obj.message;
              }
            }
          } catch (err) {
            message = err.message;
          }
        } else {
          message = `HTTP error ${xhr.status}`;
        }
        if (error && reject && typeof reject === 'function') {
          reject(new Error(message));
        }
      };
      if (data instanceof HTMLFormElement) {
        data = new FormData(data);
      }
      let body;
      if (data instanceof FormData) {
        if (method === 'POST') {
          body = data;
        } else {
          // noinspection JSCheckFunctionSignatures
          body = new URLSearchParams(data).toString();
        }
      } else {
        for (const key in data) {
          if (data.hasOwnProperty(key)) {
            if (Array.isArray(data[key])) {
              for (const value of data[key]) {
                params.push(`${key}[]=${this.encodeValue(value)}`);
              }
            } else {
              params.push(`${key}=${this.encodeValue(data[key])}`);
            }
          }
        }
        body = params.join('&');
        body = body.replace(/%20/g, '+');
      }
      if (method === 'GET' && body.length) {
        url += `?${body}`;
        body = null;
      }
      xhr.open(method, url, true);
      if (method === 'POST' && !(data instanceof FormData)) {
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
      }
      xhr.send(body);
    });
  }

  static encodeValue(value) {
    if (typeof value === 'object') {
      value = JSON.stringify(value);
    }
    return encodeURIComponent(value);
  }
}
