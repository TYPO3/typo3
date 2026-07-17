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
import{html as n}from"lit";class s{setConfig(e){this.config=e}async render(){return n`<typo3-backend-alert severity=0 heading=${this.config.labels.successTitle} message=${this.config.labels.successDescription} show-icon></typo3-backend-alert>`}async execute(){const e=this.config.data.event;if(!(e instanceof Event)){console.warn("Event finisher called without an event to dispatch");return}document.dispatchEvent(e);for(let t=0;t<window.frames.length;t++)try{window.frames[t].document.dispatchEvent(e)}catch{}}}export{s as default};
