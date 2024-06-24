/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Config from '../src/Config.js';
import Http from '../src/Http.js';
import { Initializer } from '../src/Initializer.js';
import Locale from '../src/Locale.js';
import Session from '../src/Session.js';

describe('Initializer tests', () => {

  let initializer;
  let data;

  beforeEach(() => {
    data = {
      auth: {},
      config: {},
      lang: {}
    };
    initializer = new Initializer();
    spyOn(initializer.auth, 'load');
    spyOn(initializer.config, 'load');
    spyOn(initializer.lang, 'init');
    spyOn(Http, 'get').and.resolveTo(data);
  });

  it('should create instance', () => {
    expect(initializer.auth).toBeInstanceOf(Session);
    expect(initializer.config).toBeInstanceOf(Config);
    expect(initializer.lang).toBeInstanceOf(Locale);
  });

  it('should load data from server', (done) => {
    // when
    initializer.initialize().then(() => {
      // then
      expect(Http.get).toHaveBeenCalledWith('utils/getinit.php');
      expect(initializer.auth.load).toHaveBeenCalledWith(data.auth);
      expect(initializer.config.load).toHaveBeenCalledWith(data.config);
      expect(initializer.lang.init).toHaveBeenCalledWith(initializer.config, data.lang);
      done();
    }).catch((e) => done.fail(`reject callback called (${e})`));
  });

  it('should throw error on missing data.config', (done) => {
    // given
    delete data.config;
    // when
    initializer.initialize().then(() => {
      // then
      done.fail('resolve callback called');
    }).catch((e) => {
      expect(e).toEqual(jasmine.any(Error));
      done();
    });
  });

  it('should throw error on missing data.auth', (done) => {
    // given
    delete data.auth;
    // when
    initializer.initialize().then(() => {
      // then
      done.fail('resolve callback called');
    }).catch((e) => {
      expect(e).toEqual(jasmine.any(Error));
      done();
    });
  });

  it('should throw error on missing data.lang', (done) => {
    // given
    delete data.lang;
    // when
    initializer.initialize().then(() => {
      // then
      done.fail('resolve callback called');
    }).catch((e) => {
      expect(e).toEqual(jasmine.any(Error));
      done();
    });
  });

  it('should resolve on DOMContentLoaded event', (done) => {
    // given
    spyOnProperty(document, 'readyState').and.returnValue('loading');
    // when
    Initializer.waitForDom().then(() => {
      // then
      console.log(document.readyState);
      done();
    }).catch((e) => done.fail(`reject callback called (${e})`));

    document.dispatchEvent(new Event('DOMContentLoaded'));
  });

  it('should resolve on DOM ready', (done) => {
    // given
    spyOnProperty(document, 'readyState').and.returnValue('complete');
    // when
    Initializer.waitForDom().then(() => {
      // then
      done();
    }).catch((e) => done.fail(`reject callback called (${e})`));
  });

});
