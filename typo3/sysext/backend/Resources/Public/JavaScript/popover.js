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
import{Popover as o}from"bootstrap";class l{constructor(){this.DEFAULT_SELECTOR='[data-bs-toggle="popover"]',this.initialize()}initialize(t){t=t||this.DEFAULT_SELECTOR,document.querySelectorAll(t).forEach(e=>{this.applyTitleIfAvailable(e),new o(e)})}popover(t){this.toIterable(t).forEach(e=>{this.applyTitleIfAvailable(e),new o(e)})}setOptions(t,e){e=e||{};const n=e.title||t.dataset.title||t.dataset.bsTitle||"",s=e.content||t.dataset.bsContent||"";t.dataset.bsTitle=n,t.dataset.bsOriginalTitle=n,t.dataset.bsContent=s,t.dataset.bsPlacement="auto",delete e.title,delete e.content;const a=o.getInstance(t);if(a===null){console.warn("Failed to get popover instance for element.");return}a.setContent({".popover-header":n,".popover-body":s});for(const[i,r]of Object.entries(e))a._config[i]=r}show(t){const e=o.getInstance(t);if(e===null){console.warn("Failed to get popover instance for element.");return}e.show()}hide(t){const e=o.getInstance(t);if(e===null){console.warn("Failed to get popover instance for element.");return}e.hide()}destroy(t){const e=o.getInstance(t);if(e===null){console.warn("Failed to get popover instance for element.");return}e.dispose()}toggle(t){const e=o.getInstance(t);if(e===null){console.warn("Failed to get popover instance for element.");return}e.toggle()}toIterable(t){let e;if(t instanceof HTMLElement)e=[t];else if(t instanceof NodeList)e=t;else throw`Cannot consume element of type ${t.constructor.name}, expected NodeListOf<HTMLElement> or HTMLElement`;return e}applyTitleIfAvailable(t){const e=t.title||t.dataset.title||"";e&&(t.dataset.bsTitle=e)}}var c=new l;export{c as default};
