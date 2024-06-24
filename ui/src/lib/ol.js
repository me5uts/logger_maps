/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { Control, Rotate, ScaleLine, Zoom, ZoomToExtent } from 'ol/control';
import { LineString, Point } from 'ol/geom';
import { fromLonLat, toLonLat } from 'ol/proj';
import Feature from 'ol/Feature';
import Icon from 'ol/style/Icon';
import Map from 'ol/Map';
import OSM from 'ol/source/OSM';
import Overlay from 'ol/Overlay';
import Stroke from 'ol/style/Stroke';
import Style from 'ol/style/Style';
import TileLayer from 'ol/layer/Tile';
import Vector from 'ol/source/Vector';
import VectorLayer from 'ol/layer/Vector';
import View from 'ol/View';
import XYZ from 'ol/source/XYZ';
import { containsCoordinate } from 'ol/extent.js';

export { Feature, Map, Overlay, View };
export const control = { Control, Rotate, ScaleLine, Zoom, ZoomToExtent };
export const extent = { containsCoordinate };
export const geom = { LineString, Point };
export const layer = { TileLayer, VectorLayer };
export const proj = { fromLonLat, toLonLat };
export const source = { OSM, Vector, XYZ };
export const style = { Icon, Stroke, Style };
