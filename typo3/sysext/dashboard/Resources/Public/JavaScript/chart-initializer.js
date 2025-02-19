/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import{Chart,ArcElement,LineElement,BarElement,PointElement,BarController,BubbleController,DoughnutController,LineController,PieController,PolarAreaController,RadarController,ScatterController,CategoryScale,LinearScale,LogarithmicScale,RadialLinearScale,TimeScale,TimeSeriesScale,Decimation,Filler,Legend,Title,Tooltip,SubTitle}from"@typo3/dashboard/contrib/chartjs.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{DashboardWidgetContentRenderedEvent}from"@typo3/dashboard/dashboard.js";class ChartInitializer{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){Chart.register(ArcElement,LineElement,BarElement,PointElement,BarController,BubbleController,DoughnutController,LineController,PieController,PolarAreaController,RadarController,ScatterController,CategoryScale,LinearScale,LogarithmicScale,RadialLinearScale,TimeScale,TimeSeriesScale,Decimation,Filler,Legend,Title,Tooltip,SubTitle),new RegularEvent(DashboardWidgetContentRenderedEvent.eventName,((e,r)=>{e.preventDefault();const o=e.widget.eventdata;if(void 0===o||void 0===o.graphConfig)return;const t=r.querySelector("canvas");let l;if(null!==t&&(l=t.getContext("2d")),void 0===l)return;this.darkModeEnabled()?(o.graphConfig.options.color="#ccc",o.graphConfig.options.borderColor="#000",Chart.defaults.borderColor="rgba(255,255,255,.1)",Chart.defaults.color="#ccc"):(o.graphConfig.options.color="#666",o.graphConfig.options.borderColor="#fff",Chart.defaults.borderColor="rgba(0,0,0,.1)",Chart.defaults.color="#666");const a=Chart.getChart(l);if(a)return a.data=o.graphConfig.data,a.options=o.graphConfig.options,void a.update();new Chart(l,o.graphConfig)})).delegateTo(document,this.selector)}darkModeEnabled(){const e=document.querySelector(this.selector),r=window.getComputedStyle(e).colorScheme;return"light only"!==r&&"light"!==r&&("dark only"===r||"dark"===r||window.matchMedia("(prefers-color-scheme: dark)").matches)}}export default new ChartInitializer;