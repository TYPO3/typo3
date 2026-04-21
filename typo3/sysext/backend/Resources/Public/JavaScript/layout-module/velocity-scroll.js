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
import T from"@typo3/core/document-service.js";import x from"@typo3/core/event/regular-event.js";const v=.2,E=2.5;T.ready().then(()=>{new x("dragstart",i=>{H(i)}).delegateTo(document,'[draggable="true"]')});function H(i){const r=document.scrollingElement;if(!(r instanceof HTMLElement)){console.warn("Scrolling element is not an HTMLElement. Velocity scroll will not work.");return}const s=i.target,o=S(s,"y")??r,n=S(s,"x")??r;let t=0,c=0,w=!0,g=performance.now();const p=o.style.scrollBehavior,y=n.style.scrollBehavior;o.style.scrollBehavior="auto",n.style.scrollBehavior="auto";const u=e=>{const l=(e-g)/1e3;g=e;const a=o.scrollHeight-o.clientHeight,h=n.scrollWidth-n.clientWidth;o.scrollTop=Math.max(0,Math.min(o.scrollTop+t*l,a)),n.scrollLeft=Math.max(0,Math.min(n.scrollLeft+c*l,h)),w&&requestAnimationFrame(u)};requestAnimationFrame(u);const d=(e,l,a)=>e<l?(1-e/l)**2*a:0,m=e=>{const l=window.innerHeight*v,a=window.innerWidth*v,h=window.innerHeight*E,f=window.innerWidth*E;t=d(window.innerHeight-e.clientY,l,h)-d(e.clientY,l,h),c=d(window.innerWidth-e.clientX,a,f)-d(e.clientX,a,f)};m(i),window.addEventListener("dragover",m),window.addEventListener("dragend",()=>{window.removeEventListener("dragover",m),t=0,c=0,w=!1,o.style.scrollBehavior=p,n.style.scrollBehavior=y},{once:!0})}function S(i,r){const s=r==="x"?"scrollWidth":"scrollHeight",o=r==="x"?"clientWidth":"clientHeight",n=r==="x"?"overflowX":"overflowY";let t=i;for(;t instanceof HTMLElement;){if(t[s]>t[o]){const c=window.getComputedStyle(t)[n];if(c==="auto"||c==="scroll")return t}t=t.parentElement}return null}export{H as initVelocityScroll};
