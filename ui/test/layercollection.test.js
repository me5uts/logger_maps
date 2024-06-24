/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import Layer from '../src/Layer.js';
import LayerCollection from '../src/LayerCollection.js';

describe('LayerCollection tests', () => {

  let layers;
  const testId = 5;
  const testName = 'test name';
  const testUrl = 'https://layer.url';
  const testPriority = 0;

  beforeEach(() => {
    layers = new LayerCollection();
  });

  it('should create instance', () => {
    // then
    expect(layers).toBeInstanceOf(Array);
    expect(layers).toBeInstanceOf(LayerCollection);
  });

  it('should add new layer', () => {
    // when
    layers.addNewLayer(testName, testUrl, testPriority);
    layers.addNewLayer(`${testName}2`, `${testUrl}2`, testPriority + 1);
    // then
    expect(layers.length).toBe(2);
    expect(layers[0]).toBeInstanceOf(Layer);
    expect(layers[0].id).toBe(1);
    expect(layers[0].name).toBe(testName);
    expect(layers[0].url).toBe(testUrl);
    expect(layers[0].priority).toBe(testPriority);
    expect(layers[1].id).toBe(2);
  });

  it('should add layer', () => {
    // when
    layers.addLayer(testId, testName, testUrl, testPriority);
    // then
    expect(layers.length).toBe(1);
    expect(layers[0]).toBeInstanceOf(Layer);
    expect(layers[0].id).toBe(testId);
    expect(layers[0].name).toBe(testName);
    expect(layers[0].url).toBe(testUrl);
    expect(layers[0].priority).toBe(testPriority);
  });

  it('should delete layer by id', () => {
    // given
    layers.addLayer(testId, testName, testUrl, testPriority);
    layers.addLayer(testId + 1, testName, testUrl, testPriority);

    expect(layers.length).toBe(2);
    // when
    layers.delete(testId);

    // then
    expect(layers.length).toBe(1);
    expect(layers[0].id).toBe(testId + 1);
  });

  it('should get layer by id (numeric)', () => {
    // when
    layers.addLayer(testId, testName, testUrl, testPriority);
    layers.addLayer(testId + 1, testName, testUrl, testPriority);
    // then
    expect(layers.get(testId).id).toBe(testId);
  });

  it('should get max id of all layers in array', () => {
    // when
    layers.addLayer(testId + 1, testName, testUrl, testPriority);
    layers.addLayer(testId, testName, testUrl, testPriority);
    // then
    expect(layers.getMaxId()).toBe(testId + 1);
  });

  it('should set priority layer by id', () => {
    // given
    layers.addLayer(testId + 1, testName, testUrl, testPriority);
    layers.addLayer(testId, testName, testUrl, testPriority);
    // when
    layers.setPriorityLayer(testId);
    // then
    expect(layers[0].priority).toBe(0);
    expect(layers[1].priority).toBe(1);
    expect(layers.getPriorityLayer()).toBe(testId);
  });

  it('should load layers from array', () => {
    // given
    const arr = [ { id: testId, name: testName, url: testUrl, priority: testPriority } ];
    // when
    layers.load(arr);
    // then
    expect(layers.length).toBe(1);
    expect(layers[0]).toBeInstanceOf(Layer);
    expect(layers[0].id).toBe(testId);
    expect(layers[0].name).toBe(testName);
    expect(layers[0].url).toBe(testUrl);
    expect(layers[0].priority).toBe(testPriority);
  });

});
