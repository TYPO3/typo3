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
import{Popover as BootstrapPopover}from"bootstrap";class Popover{constructor(){this.DEFAULT_SELECTOR='[data-bs-toggle="popover"]',this.initialize()}initialize(t){t=t||this.DEFAULT_SELECTOR,document.querySelectorAll(t).forEach((t=>{this.applyTitleIfAvailable(t),new BootstrapPopover(t)}))}popover(t){this.toIterable(t).forEach((t=>{this.applyTitleIfAvailable(t),new BootstrapPopover(t)}))}setOptions(t,e){const o=(e=e||{}).title||t.dataset.title||t.dataset.bsTitle||"",s=e.content||t.dataset.bsContent||"";t.dataset.bsTitle=o,t.dataset.bsOriginalTitle=o,t.dataset.bsContent=s,t.dataset.bsPlacement="auto",delete e.title,delete e.content;const a=BootstrapPopover.getInstance(t);a.setContent({".popover-header":o,".popover-body":s});for(const[t,o]of Object.entries(e))a._config[t]=o}show(t){BootstrapPopover.getInstance(t).show()}hide(t){BootstrapPopover.getInstance(t).hide()}destroy(t){BootstrapPopover.getInstance(t).dispose()}toggle(t){BootstrapPopover.getInstance(t).toggle()}toIterable(t){let e;if(t instanceof HTMLElement)e=[t];else{if(!(t instanceof NodeList))throw`Cannot consume element of type ${t.constructor.name}, expected NodeListOf<HTMLElement> or HTMLElement`;e=t}return e}applyTitleIfAvailable(t){const e=t.title||t.dataset.title||"";e&&(t.dataset.bsTitle=e)}}export default new Popover;