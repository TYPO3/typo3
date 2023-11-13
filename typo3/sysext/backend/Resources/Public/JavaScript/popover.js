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
import{Popover as BootstrapPopover}from"bootstrap";class Popover{constructor(){this.DEFAULT_SELECTOR='[data-bs-toggle="popover"]',this.initialize()}initialize(e){e=e||this.DEFAULT_SELECTOR,document.querySelectorAll(e).forEach((e=>{this.applyTitleIfAvailable(e),new BootstrapPopover(e)}))}popover(e){this.toIterable(e).forEach((e=>{this.applyTitleIfAvailable(e),new BootstrapPopover(e)}))}setOptions(e,t){const o=(t=t||{}).title||e.dataset.title||e.dataset.bsTitle||"",n=t.content||e.dataset.bsContent||"";e.dataset.bsTitle=o,e.dataset.bsOriginalTitle=o,e.dataset.bsContent=n,e.dataset.bsPlacement="auto",delete t.title,delete t.content;const s=BootstrapPopover.getInstance(e);if(null!==s){s.setContent({".popover-header":o,".popover-body":n});for(const[e,o]of Object.entries(t))s._config[e]=o}else console.warn("Failed to get popover instance for element.")}show(e){const t=BootstrapPopover.getInstance(e);null!==t?t.show():console.warn("Failed to get popover instance for element.")}hide(e){const t=BootstrapPopover.getInstance(e);null!==t?t.hide():console.warn("Failed to get popover instance for element.")}destroy(e){const t=BootstrapPopover.getInstance(e);null!==t?t.dispose():console.warn("Failed to get popover instance for element.")}toggle(e){const t=BootstrapPopover.getInstance(e);null!==t?t.toggle():console.warn("Failed to get popover instance for element.")}toIterable(e){let t;if(e instanceof HTMLElement)t=[e];else{if(!(e instanceof NodeList))throw`Cannot consume element of type ${e.constructor.name}, expected NodeListOf<HTMLElement> or HTMLElement`;t=e}return t}applyTitleIfAvailable(e){const t=e.title||e.dataset.title||"";t&&(e.dataset.bsTitle=t)}}export default new Popover;