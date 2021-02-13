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
define(["require","exports","bootstrap","TYPO3/CMS/Core/DocumentService"],(function(t,e,o,n){"use strict";class s{static applyAttributes(t,e){for(const[o,n]of Object.entries(t))e.setAttribute(o,n)}constructor(){n.ready().then(()=>{this.initialize('[data-bs-toggle="tooltip"]')})}initialize(t,e={}){const n=document.querySelectorAll(t);for(const t of n)new o.Tooltip(t,e)}show(t,e){const n={"data-bs-placement":"auto",title:e};if(t instanceof NodeList||t instanceof HTMLElement){if(t instanceof NodeList)for(const e of t)s.applyAttributes(n,e),o.Tooltip.getInstance(e).show();else if(t instanceof HTMLElement)return s.applyAttributes(n,t),void o.Tooltip.getInstance(t).show()}else{console.warn("Passing an jQuery object to Tooltip.show() has been marked as deprecated. Either pass a NodeList or an HTMLElement.");for(const[e,o]of Object.entries(n))t.attr(e,o);t.tooltip("show")}}hide(t){if(!(t instanceof NodeList||t instanceof HTMLElement))return console.warn("Passing an jQuery object to Tooltip.hide() has been marked as deprecated. Either pass a NodeList or an HTMLElement."),void t.tooltip("hide");if(t instanceof NodeList)for(const e of t)o.Tooltip.getInstance(e).hide();else t instanceof HTMLElement&&o.Tooltip.getInstance(t).hide()}}const i=new s;return TYPO3.Tooltip=i,i}));