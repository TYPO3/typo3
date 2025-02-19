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
class s{constructor(e,a,n=!1){this.eventName=e,this.callback=a,this.options=n}bindTo(e){if(!e){console.warn(`Binding event ${this.eventName} failed, element was not found.`);return}this.boundElement=e,e.addEventListener(this.eventName,this.callback,this.options)}delegateTo(e,a){if(!e){console.warn(`Delegating event ${this.eventName} failed, element was not found.`);return}this.boundElement=e,e.addEventListener(this.eventName,n=>{for(let t=n.target;t&&t!==this.boundElement;t=t.parentElement)if(t.matches(a)){this.callback.call(t,n,t);break}},this.options)}release(){this.boundElement.removeEventListener(this.eventName,this.callback)}}export{s as default};
