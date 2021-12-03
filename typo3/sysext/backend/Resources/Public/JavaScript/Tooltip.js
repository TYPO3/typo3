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
define(["require","exports","bootstrap","TYPO3/CMS/Core/DocumentService"],(function(t,e,o,n){"use strict";class i{static applyAttributes(t,e){for(const[o,n]of Object.entries(t))e.setAttribute(o,n)}constructor(){n.ready().then(()=>{this.initialize('[data-bs-toggle="tooltip"]')})}initialize(t,e={}){0===Object.entries(e).length&&(e={container:"body",trigger:"hover",delay:{show:500,hide:100}});const n=document.querySelectorAll(t);for(const t of n)o.Tooltip.getOrCreateInstance(t,e)}show(t,e){const n={"data-bs-placement":"auto",title:e};if(t instanceof NodeList)for(const e of t)i.applyAttributes(n,e),o.Tooltip.getInstance(e).show();else if(t instanceof HTMLElement)return i.applyAttributes(n,t),void o.Tooltip.getInstance(t).show()}hide(t){if(t instanceof NodeList)for(const e of t){const t=o.Tooltip.getInstance(e);null!==t&&t.hide()}else t instanceof HTMLElement&&o.Tooltip.getInstance(t).hide()}}const s=new i;return TYPO3.Tooltip=s,s}));