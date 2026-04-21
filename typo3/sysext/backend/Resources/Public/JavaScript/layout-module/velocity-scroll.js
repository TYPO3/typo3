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
import S from"@typo3/core/document-service.js";import T from"@typo3/core/event/regular-event.js";const u=.2,f=2.5;S.ready().then(()=>{new T("dragstart",l=>{E(l)}).delegateTo(document,'[draggable="true"]')});function E(l){const t=document.scrollingElement;if(!(t instanceof HTMLElement)){console.warn("Scrolling element is not an HTMLElement. Velocity scroll will not work.");return}const e=p(l.target)??t;let a=0,s=0,m=!0,h=performance.now();const v=t.style.scrollBehavior,H=e.style.scrollBehavior;t.style.scrollBehavior="auto",e.style.scrollBehavior="auto";const w=o=>{const n=(o-h)/1e3;h=o;const r=t.scrollHeight-t.clientHeight,c=e.scrollWidth-e.clientWidth;t.scrollTop=Math.max(0,Math.min(t.scrollTop+a*n,r)),e.scrollLeft=Math.max(0,Math.min(e.scrollLeft+s*n,c)),m&&requestAnimationFrame(w)};requestAnimationFrame(w);const i=(o,n,r)=>o<n?(1-o/n)**2*r:0,d=o=>{const n=window.innerHeight*u,r=window.innerWidth*u,c=window.innerHeight*f,g=window.innerWidth*f;a=i(window.innerHeight-o.clientY,n,c)-i(o.clientY,n,c),s=i(window.innerWidth-o.clientX,r,g)-i(o.clientX,r,g)};d(l),window.addEventListener("dragover",d),window.addEventListener("dragend",()=>{window.removeEventListener("dragover",d),a=0,s=0,m=!1,t.style.scrollBehavior=v,e.style.scrollBehavior=H},{once:!0})}function p(l){let t=l;for(;t instanceof HTMLElement;){if(t.scrollWidth>t.clientWidth){const e=window.getComputedStyle(t).overflowX;if(e==="auto"||e==="scroll")return t}t=t.parentElement}return null}export{E as initVelocityScroll};
