/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

import { AutoScaleAxis, LineChart } from 'chartist';
import { lang as $ } from '../Initializer.js';
import Observer from '../Observer.js';
import ViewModel from '../ViewModel.js';
import ctAxisTitle from 'chartist-plugin-axistitle';

/**
 * @typedef {Object} PlotPoint
 * @property {number} x
 * @property {number} y
 */
/**
 * @typedef {PlotPoint[]} PlotData
 */

// FIXME: Chartist is not suitable for large data sets
const LARGE_DATA = 1000;
export default class ChartViewModel extends ViewModel {
  /**
   * @param {State} state
   */
  constructor(state) {
    super({
      pointSelected: null,
      chartVisible: false,
      buttonVisible: false,
      onChartToggle: null,
      onMenuToggle: null
    });
    this.state = state;
    /** @type {PlotData} */
    this.data = [];
    /** @type {?LineChart} */
    this.chart = null;
    /** @type {?NodeListOf<SVGLineElement>} */
    this.chartPoints = null;
    /** @type {HTMLDivElement} */
    this.chartElement = document.querySelector('#chart');
    /** @type {HTMLDivElement} */
    this.chartContainer = this.chartElement.parentElement;
    /** @type {HTMLAnchorElement} */
    this.buttonElement = document.querySelector('#altitudes');
  }

  /**
   * @return {ChartViewModel}
   */
  init() {
    this.chartSetup();
    this.setObservers();
    this.bindAll();
    return this;
  }

  chartSetup() {
    ChartViewModel.loadCss();
    // Utils.addCss('css/dist/chartist.css', 'chartist_css');
    this.chart = ChartViewModel.getChart(this.chartElement, this.data);
    this.chart.on('created', () => this.onCreated());
  }

  static loadCss() {
    import('chartist/dist/index.css').catch((e) => {
      console.error('css loading failed', e)
    });
  }

  static getChart(element, data) {
    return new LineChart(element, {
      series: [ data ]
    }, {
      lineSmooth: true,
      showArea: true,
      axisX: {
        type: AutoScaleAxis,
        onlyInteger: true,
        showLabel: false
      },
      plugins: [
        ctAxisTitle({
          axisY: {
            axisTitle: `${$._('altitude')} (${$.unit('unitDistance')} ${$.unit('unitAltitude')})`,
            axisClass: 'ct-axis-title',
            offset: {
              x: 0,
              y: 11
            },
            textAnchor: 'middle',
            flipTitle: true
          }
        })
      ]
    });
  }

  onCreated() {
    if (this.data.length && this.data.length <= LARGE_DATA) {
      this.chartPoints = document.querySelectorAll('.ct-series .ct-point');
      const len = this.chartPoints.length;
      for (let id = 0; id < len; id++) {
        this.chartPoints[id].addEventListener('click', () => {
          this.model.pointSelected = id;
        });
      }
    }
  }

  setObservers() {
    this.state.onChanged('currentTrack', (track) => {
      if (track) {
        Observer.observe(track, 'positions', () => {
          this.onTrackUpdate(track, true);
        });
      }
      this.onTrackUpdate(track);
    });
    this.onChanged('buttonVisible', (visible) => this.renderButton(visible));
    this.onChanged('chartVisible', (visible) => this.renderContainer(visible));
    this.model.onChartToggle = () => {
      this.model.chartVisible = !this.model.chartVisible;
    };
    this.model.onMenuToggle = () => {
      if (this.model.chartVisible) {
        this.chart.update();
      }
    };
  }

  /**
   * @param {?Track} track
   * @param {boolean=} update
   */
  onTrackUpdate(track, update = false) {
    this.render(track, update);
    this.model.buttonVisible = !!track && track.hasPlotData;
  }

  /**
   * @param {boolean} isVisible
   */
  renderContainer(isVisible) {
    if (isVisible) {
      this.chartContainer.style.display = 'block';
      this.render(this.state.currentTrack);
    } else {
      this.chartContainer.style.display = 'none';
    }
  }

  /**
   * @param {boolean} isVisible
   */
  renderButton(isVisible) {
    if (isVisible) {
      this.buttonElement.classList.remove('menu-hidden');
    } else {
      this.buttonElement.classList.add('menu-hidden');
    }
  }

  /**
   * @param {?Track} track
   * @param {boolean=} update
   */
  render(track, update = false) {
    let data = [];
    if (track && track.hasPlotData && this.model.chartVisible) {
      data = track.plotData;
    } else {
      this.model.chartVisible = false;
    }
    if (update || this.data !== data) {
      console.log(`Chart${update ? ' forced' : ''} update (${data.length})`);
      this.data = data;
      const options = {
        lineSmooth: (data.length <= LARGE_DATA)
      };
      this.chart.update({ series: [ data ] }, options, true);
    }
  }

  /**
   * @param {number} pointId
   * @param {string} $className
   */
  pointAddClass(pointId, $className) {
    if (this.model.chartVisible && this.chartPoints && this.chartPoints.length > pointId) {
      const point = this.chartPoints[pointId];
      point.classList.add($className);
    }
  }

  /**
   * @param {string} $className
   */
  pointsRemoveClass($className) {
    if (this.model.chartVisible && this.chartPoints) {
      this.chartPoints.forEach((el) => el.classList.remove($className));
    }
  }

  /**
   * @param {number} pointId
   */
  onPointOver(pointId) {
    this.pointAddClass(pointId, 'ct-point-hilight');
  }

  onPointOut() {
    this.pointsRemoveClass('ct-point-hilight');
  }

  /**
   * @param {number} pointId
   */
  onPointSelect(pointId) {
    this.pointAddClass(pointId, 'ct-point-selected');
  }

  onPointUnselect() {
    this.pointsRemoveClass('ct-point-selected');
  }
}
