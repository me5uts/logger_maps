/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

const baseUrl = '/base/test/fixtures/';

export default class Fixture {

  static load(url) {
    return this.get(url).then((fixture) => {
      document.body.insertAdjacentHTML('afterbegin', fixture);
    });
  }

  static clear() {
    document.body.innerHTML = '';
  }

  /**
   * @param {string} url
   * @return {Promise<string, Error>}
   */
  static get(url) {
    url = baseUrl + url;
    const xhr = new XMLHttpRequest();
    return new Promise((resolve, reject) => {
      xhr.onreadystatechange = () => {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          if (xhr.status === 200) {
            resolve(xhr.responseText);
          } else {
            reject(new Error(`HTTP error ${xhr.status}`));
          }
        }
      };
      xhr.open('GET', url, true);
      xhr.send();
    });
  }
}
