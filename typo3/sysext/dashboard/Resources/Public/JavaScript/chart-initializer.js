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
import{Chart,ArcElement,LineElement,BarElement,PointElement,BarController,BubbleController,DoughnutController,LineController,PieController,PolarAreaController,RadarController,ScatterController,CategoryScale,LinearScale,LogarithmicScale,RadialLinearScale,TimeScale,TimeSeriesScale,Decimation,Filler,Legend,Title,Tooltip,SubTitle}from"@typo3/dashboard/contrib/chartjs.js";import RegularEvent from"@typo3/core/event/regular-event.js";class ChartInitializer{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){Chart.register(ArcElement,LineElement,BarElement,PointElement,BarController,BubbleController,DoughnutController,LineController,PieController,PolarAreaController,RadarController,ScatterController,CategoryScale,LinearScale,LogarithmicScale,RadialLinearScale,TimeScale,TimeSeriesScale,Decimation,Filler,Legend,Title,Tooltip,SubTitle),new RegularEvent("widgetContentRendered",(function(e){e.preventDefault();const r=e.detail;if(void 0===r||void 0===r.graphConfig)return;const t=this.querySelector("canvas");let l;null!==t&&(l=t.getContext("2d")),void 0!==l&&new Chart(l,r.graphConfig)})).delegateTo(document,this.selector)}}export default new ChartInitializer;