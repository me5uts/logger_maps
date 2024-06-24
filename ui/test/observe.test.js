/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Observer from '../src/Observer.js';

describe('Observe tests', () => {
  let object;
  let result = false;
  let resultValue;

  beforeEach(() => {
    object = { observed: 1, nonObserved: 1 };
    result = false;
    // eslint-disable-next-line no-undefined
    resultValue = undefined;
  });

  describe('when object is observed', () => {

    it('should throw error if observer is missing', () => {
      expect(() => { Observer.observe(object, 'observed'); }).toThrow(new Error('Invalid argument for observe'));
    });

    it('should notify observers when observed property is modified', () => {
      // given
      Observer.observe(object, 'observed', (value) => {
        result = true;
        resultValue = value;
      });
      // when
      expect(result).toBe(false);
      object.observed = 2;
      // then
      expect(result).toBe(true);
      expect(resultValue).toBe(2);
    });

    it('should notify multiple observers when observed property is modified', () => {
      // given
      let result2 = false;
      let resultValue2;
      Observer.observe(object, 'observed', (value) => {
        result = true;
        resultValue = value;
      });
      Observer.observe(object, 'observed', (value) => {
        result2 = true;
        resultValue2 = value;
      });
      // when
      expect(result).toBe(false);
      expect(result2).toBe(false);
      object.observed = 2;
      // then
      expect(result).toBe(true);
      expect(resultValue).toBe(2);
      expect(result2).toBe(true);
      // noinspection JSUnusedAssignment
      expect(resultValue2).toBe(2);
    });

    it('should not notify observers when non-observed property is modified', () => {
      // given
      Observer.observe(object, 'observed', () => {
        result = true;
      });
      // when
      expect(result).toBe(false);
      object.nonObserved = 2;
      // then
      expect(result).toBe(false);
    });

    it('should not notify observers when modified value is same', () => {
      // given
      Observer.observe(object, 'observed', () => {
        result = true;
      });
      // when
      expect(result).toBe(false);
      object.observed = 1;
      // then
      expect(result).toBe(false);
    });

    it('should notify observers when any property is modified', () => {
      // given
      Observer.observe(object, (value) => {
        result = true;
        resultValue = value;
      });
      // when
      expect(result).toBe(false);
      object.observed = 2;
      // then
      expect(result).toBe(true);
      expect(resultValue).toBe(2);

      // given
      result = false;
      resultValue = null;

      // when
      expect(result).toBe(false);
      object.nonObserved = 2;
      // then
      expect(result).toBe(true);
      expect(resultValue).toBe(2);
    });

    it('should notify observers when observed array property is modified', () => {
      // given
      const array = [ 1, 2 ];
      object = { array };
      Observer.observe(object, 'array', (value) => {
        result = true;
        resultValue = value;
      });
      // when
      expect(result).toBe(false);
      array.push(3);
      // then
      expect(result).toBe(true);
      expect(resultValue).toEqual(array);
    });

    it('should notify observers when observed array is modified', () => {
      // given
      const array = [ 1, 2 ];
      Observer.observe(array, (value) => {
        result = true;
        resultValue = value;
      });
      // when
      expect(result).toBe(false);
      array.push(3);
      // then
      expect(result).toBe(true);
      expect(resultValue).toEqual(array);
    });

    it('should retain observers after array is reassigned', () => {
      // given
      let result2 = false;
      let resultValue2;
      const array = [ 1, 2 ];
      const newArray = [ 3, 4 ];
      object = { array };
      Observer.observe(object, 'array', (value) => {
        result = true;
        resultValue = value;
      });
      Observer.observe(object, 'array', (value) => {
        result2 = true;
        resultValue2 = value;
      });
      // when
      object.array = newArray;
      result = false;
      result2 = false;

      expect(result).toBe(false);
      expect(result2).toBe(false);
      object.array.push(5);
      // then
      expect(result).toBe(true);
      expect(result2).toBe(true);
      expect(resultValue).toEqual(newArray);
      // noinspection JSUnusedAssignment
      expect(resultValue2).toEqual(newArray);
    });

    it('should retain observers after array property is silently set', () => {
      // given
      let result2 = false;
      let resultValue2;
      const array = [ 1, 2 ];
      const newArray = [ 3, 4 ];
      object = { array: [] };
      Observer.observe(object, 'array', (value) => {
        result = true;
        resultValue = value;
      });
      Observer.observe(object, 'array', (value) => {
        result2 = true;
        resultValue2 = value;
      });
      // when
      Observer.setSilently(object, 'array', array);
      object.array = newArray;
      result = false;
      result2 = false;

      expect(result).toBe(false);
      expect(result2).toBe(false);
      object.array.push(5);
      // then
      expect(result).toBe(true);
      expect(result2).toBe(true);
      expect(resultValue).toEqual(newArray);
      // noinspection JSUnusedAssignment
      expect(resultValue2).toEqual(newArray);
    });
  });

  describe('when object is unobserved', () => {

    it('should throw error if removed observer is missing', () => {
      expect(() => {
        Observer.unobserve(object, 'unobserved');
      }).toThrow(new Error('Invalid argument for unobserve'));
    });

    it('should not notify observers when unobserved property is modified', () => {
      // given
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      Observer.observe(object, 'observed', observer);
      // when
      Observer.unobserve(object, 'observed', observer);

      expect(result).toBe(false);
      object.observed = 2;
      // then
      expect(result).toBe(false);
      // eslint-disable-next-line no-undefined
      expect(resultValue).toBe(undefined);
      expect(object.observed).toBe(2);
    });

    it('should not notify observers when any unobserved object property is modified', () => {
      // given
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      Observer.observe(object, observer);
      // when
      Observer.unobserve(object, observer);

      expect(result).toBe(false);
      object.observed = 2;
      // then
      expect(result).toBe(false);
      // eslint-disable-next-line no-undefined
      expect(resultValue).toBe(undefined);
      expect(object.observed).toBe(2);

      // given
      result = false;
      // eslint-disable-next-line no-undefined
      resultValue = undefined;

      // when
      expect(result).toBe(false);
      object.nonObserved = 2;
      // then
      expect(result).toBe(false);
      // eslint-disable-next-line no-undefined
      expect(resultValue).toBe(undefined);
      expect(object.nonObserved).toBe(2);
    });

    it('should not notify observers when unobserved array property is modified', () => {
      // given
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const array = [ 1, 2 ];
      object = { array };
      Observer.observe(object, 'array', observer);
      // when
      Observer.unobserve(object, 'array', observer);

      expect(result).toBe(false);
      array.push(3);
      // then
      expect(result).toBe(false);
      // eslint-disable-next-line no-undefined
      expect(resultValue).toEqual(undefined);
      expect(array).toEqual([ 1, 2, 3 ]);
    });

    it('should not notify observers when unobserved array is modified', () => {
      // given
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const array = [ 1, 2 ];
      Observer.observe(array, observer);
      // when
      Observer.unobserve(array, observer);

      expect(result).toBe(false);
      array.push(3);
      // then
      expect(result).toBe(false);
      // eslint-disable-next-line no-undefined
      expect(resultValue).toEqual(undefined);
      expect(array).toEqual([ 1, 2, 3 ]);
    });

    it('should remove one of two observers of object property', () => {
      // given
      let result2 = false;
      let resultValue2;
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const observer2 = (value) => {
        result2 = true;
        resultValue2 = value;
      };
      Observer.observe(object, 'observed', observer);
      Observer.observe(object, 'observed', observer2);
      // when
      Observer.unobserve(object, 'observed', observer2);

      expect(result).toBe(false);
      expect(result2).toBe(false);
      object.observed = 2;
      // then
      expect(result).toBe(true);
      expect(resultValue).toBe(2);
      expect(result2).toBe(false);
      // noinspection JSUnusedAssignment
      expect(resultValue2).toBe(undefined);// eslint-disable-line no-undefined
    });

    it('should remove one of two observers from array', () => {
      // given
      let result2 = false;
      let resultValue2;
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const observer2 = (value) => {
        result2 = true;
        resultValue2 = value;
      };
      const array = [ 1, 2 ];
      Observer.observe(array, observer);
      Observer.observe(array, observer2);
      // when
      Observer.unobserve(array, observer2);

      expect(result).toBe(false);
      expect(result2).toBe(false);
      array.push(3);
      // then
      expect(result).toBe(true);
      expect(result2).toBe(false);
      expect(resultValue).toEqual(array);
      // noinspection JSUnusedAssignment
      expect(resultValue2).toEqual(undefined);// eslint-disable-line no-undefined
      expect(array).toEqual([ 1, 2, 3 ]);
    });

    it('should remove all observers of object property', () => {
      // given
      let result2 = false;
      let resultValue2;
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const observer2 = (value) => {
        result2 = true;
        resultValue2 = value;
      };
      Observer.observe(object, 'observed', observer);
      Observer.observe(object, 'observed', observer2);
      // when
      Observer.unobserveAll(object, 'observed');

      expect(result).toBe(false);
      expect(result2).toBe(false);
      object.observed = 2;
      // then
      expect(result).toBe(false);
      expect(resultValue).toBe(undefined);// eslint-disable-line no-undefined
      expect(result2).toBe(false);
      // noinspection JSUnusedAssignment
      expect(resultValue2).toBe(undefined);// eslint-disable-line no-undefined
      expect(object.observed).toBe(2);
    });

    it('should remove all observers from array property', () => {
      // given
      let result2 = false;
      let resultValue2;
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const observer2 = (value) => {
        result2 = true;
        resultValue2 = value;
      };
      const array = [ 1, 2 ];
      object.arr = array;
      Observer.observe(object, 'arr', observer);
      Observer.observe(object, 'arr', observer2);
      // when
      Observer.unobserveAll(object, 'arr');

      expect(result).toBe(false);
      expect(result2).toBe(false);
      array.push(3);
      // then
      expect(result).toBe(false);
      expect(result2).toBe(false);
      expect(resultValue).toEqual(undefined);// eslint-disable-line no-undefined
      // noinspection JSUnusedAssignment
      expect(resultValue2).toEqual(undefined);// eslint-disable-line no-undefined
      expect(object.arr).toEqual([ 1, 2, 3 ]);
    });

    it('should remove all observers of all object properties', () => {
      // given
      let result2 = false;
      let resultValue2;
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const observer2 = (value) => {
        result2 = true;
        resultValue2 = value;
      };
      object.observed2 = null;
      Observer.observe(object, 'observed', observer);
      Observer.observe(object, 'observed2', observer2);
      // when
      Observer.unobserveAll(object);

      expect(result).toBe(false);
      expect(result2).toBe(false);
      object.observed = 2;
      object.observed2 = 2;
      // then
      expect(result).toBe(false);
      expect(resultValue).toBe(undefined);// eslint-disable-line no-undefined
      expect(result2).toBe(false);
      // noinspection JSUnusedAssignment
      expect(resultValue2).toBe(undefined);// eslint-disable-line no-undefined
      expect(object.observed).toBe(2);
      expect(object.observed2).toBe(2);
    });

    it('should throw error when observing non-existing property', () => {
      // given
      const nonExisting = '___non-existing___';

      expect(object.hasOwnProperty(nonExisting)).toBe(false);
      // then
      expect(() => Observer.observe(object, nonExisting, (value) => {
        result = true;
        resultValue = value;
      })).toThrow();

      expect(object.hasOwnProperty(nonExisting)).toBe(false);
    });

    it('should throw error when observing non-object', () => {
      // given
      const nonExisting = '___non-existing___';
      // then
      expect(() => Observer.observe(nonExisting, (value) => {
        result = true;
        resultValue = value;
      })).toThrow();
    });

    it('should throw error when observing null object', () => {
      // given
      const nullObject = null;
      // then
      expect(() => Observer.observe(nullObject, (value) => {
        result = true;
        resultValue = value;
      })).toThrow();
    });

    it('should not notify observers when observed property is silently changed', () => {
      // given
      Observer.observe(object, 'observed', (value) => {
        result = true;
        resultValue = value;
      });
      // when
      expect(result).toBe(false);
      Observer.setSilently(object, 'observed', 2);
      // then
      expect(result).toBe(false);
      // eslint-disable-next-line no-undefined
      expect(resultValue).toBe(undefined);
    });

    it('should return true if property is observed', () => {
      // when
      Observer.observe(object, 'observed', (value) => {
        result = true;
        resultValue = value;
      });
      // then
      expect(Observer.isObserved(object, 'observed')).toBe(true);
    });

    it('should return false if property is not observed', () => {
      // when
      Observer.observe(object, 'observed', (value) => {
        result = true;
        resultValue = value;
      });
      // then
      expect(Observer.isObserved(object, 'nonObserved')).toBe(false);
    });

    it('should return true if array property is observed', () => {
      // when
      const array = [ 1, 2 ];
      object = { array };
      Observer.observe(object, 'array', (value) => {
        result = true;
        resultValue = value;
      });
      // then
      expect(Observer.isObserved(object, 'array')).toBe(true);
    });

    it('should return false if property is unobserved', () => {
      // when
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      Observer.observe(object, 'observed', observer);
      Observer.unobserve(object, 'observed', observer);
      // then
      expect(Observer.isObserved(object, 'observed')).toBe(false);
    });

    it('should return true if property is observed by given observer', () => {
      // when
      const observer = (value) => {
        result = true;
        resultValue = value;
      };
      const observer2 = () => {/* ignored */};
      Observer.observe(object, 'observed', observer);
      // then
      expect(Observer.isObserved(object, 'observed', observer)).toBe(true);
      expect(Observer.isObserved(object, 'observed', observer2)).toBe(false);
    });
  });

  describe('when notify is called directly', () => {
    it('should call observers with given value', () => {
      // given
      const observers = new Set();
      let result2 = false;
      observers.add((value) => { result = value; });
      observers.add((value) => { result2 = value; });
      // when
      expect(result).toBe(false);
      expect(result2).toBe(false);
      Observer.notify(observers, true);
      // then
      expect(result).toBe(true);
      expect(result2).toBe(true);
    });

  });
});
