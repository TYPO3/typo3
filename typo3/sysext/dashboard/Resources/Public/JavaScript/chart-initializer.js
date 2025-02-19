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
import{Chart as t,ArcElement as i,LineElement as c,BarElement as d,PointElement as s,BarController as g,BubbleController as f,DoughnutController as u,LineController as h,PieController as C,PolarAreaController as m,RadarController as p,ScatterController as S,CategoryScale as b,LinearScale as v,LogarithmicScale as w,RadialLinearScale as y,TimeScale as E,TimeSeriesScale as L,Decimation as T,Filler as k,Legend as x,Title as z,Tooltip as D,SubTitle as R}from"chart.js";import B from"@typo3/core/event/regular-event.js";import{DashboardWidgetContentRenderedEvent as M}from"@typo3/dashboard/dashboard.js";class P{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){t.register(i,c,d,s,g,f,u,h,C,m,p,S,b,v,w,y,E,L,T,k,x,z,D,R),new B(M.eventName,(r,a)=>{r.preventDefault();const e=r.widget.eventdata;if(e===void 0||e.graphConfig===void 0)return;const l=a.querySelector("canvas");let o;if(l!==null&&(o=l.getContext("2d")),o===void 0)return;this.darkModeEnabled()?(e.graphConfig.options.color="#ccc",e.graphConfig.options.borderColor="#000",t.defaults.borderColor="rgba(255,255,255,.1)",t.defaults.color="#ccc"):(e.graphConfig.options.color="#666",e.graphConfig.options.borderColor="#fff",t.defaults.borderColor="rgba(0,0,0,.1)",t.defaults.color="#666");const n=t.getChart(o);if(n){n.data=e.graphConfig.data,n.options=e.graphConfig.options,n.update();return}new t(o,e.graphConfig)}).delegateTo(document,this.selector)}darkModeEnabled(){const r=document.querySelector(this.selector),e=window.getComputedStyle(r).colorScheme;return e==="light only"||e==="light"?!1:e==="dark only"||e==="dark"?!0:window.matchMedia("(prefers-color-scheme: dark)").matches}}var q=new P;export{q as default};
