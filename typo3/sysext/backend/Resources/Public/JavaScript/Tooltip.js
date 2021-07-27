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
define(["require","exports","bootstrap","TYPO3/CMS/Core/DocumentService"],(function(e,t,o,n){"use strict";class s{static applyAttributes(e,t){for(const[o,n]of Object.entries(e))t.setAttribute(o,n)}constructor(){n.ready().then(()=>{this.initialize('[data-bs-toggle="tooltip"]')})}initialize(e,t={}){0===Object.entries(t).length&&(t={container:"body",trigger:"hover",delay:{show:500,hide:100}});const n=document.querySelectorAll(e);for(const e of n)o.Tooltip.getOrCreateInstance(e,t)}show(e,t){const n={"data-bs-placement":"auto",title:t};if(e instanceof NodeList||e instanceof HTMLElement){if(e instanceof NodeList)for(const t of e)s.applyAttributes(n,t),o.Tooltip.getInstance(t).show();else if(e instanceof HTMLElement)return s.applyAttributes(n,e),void o.Tooltip.getInstance(e).show()}else{console.warn("Passing an jQuery object to Tooltip.show() has been marked as deprecated. Either pass a NodeList or an HTMLElement.");for(const[t,o]of Object.entries(n))e.attr(t,o);e.tooltip("show")}}hide(e){if(!(e instanceof NodeList||e instanceof HTMLElement))return console.warn("Passing an jQuery object to Tooltip.hide() has been marked as deprecated. Either pass a NodeList or an HTMLElement."),void e.tooltip("hide");if(e instanceof NodeList)for(const t of e){const e=o.Tooltip.getInstance(t);null!==e&&e.hide()}else e instanceof HTMLElement&&o.Tooltip.getInstance(e).hide()}}const i=new s;return TYPO3.Tooltip=i,i}));