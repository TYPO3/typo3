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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.DistributionImage=void 0;class i extends HTMLElement{constructor(){super(...arguments),this.onError=()=>{this.image.length&&this.imageElement.getAttribute("src")===this.welcomeImage?this.imageElement.setAttribute("src",this.image):this.fallback.length&&this.imageElement.setAttribute("src",this.fallback)}}connectedCallback(){if(this.image=this.getAttribute("image")||"",this.welcomeImage=this.getAttribute("welcomeImage")||"",this.fallback=this.getAttribute("fallback")||"",!this.image.length&&!this.fallback.length)return;this.attachShadow({mode:"open"}),this.imageElement=document.createElement("img");const t=this.getAttribute("alt")||"";t.length&&this.imageElement.setAttribute("alt",t);const e=this.getAttribute("title")||"";e.length&&this.imageElement.setAttribute("title",e),this.welcomeImage.length?(this.imageElement.addEventListener("error",this.onError),this.imageElement.setAttribute("src",this.welcomeImage)):this.image.length?(this.imageElement.addEventListener("error",this.onError),this.imageElement.setAttribute("src",this.image)):this.imageElement.setAttribute("src",this.fallback);const i=document.createElement("style");i.textContent="\n      img {\n        display: block;\n        width: 300px;\n        height: auto;\n      }\n    ",this.shadowRoot.append(this.imageElement,i)}disconnectedCallback(){this.image.length&&null!==this.imageElement&&this.imageElement.removeEventListener("error",this.onError)}}e.DistributionImage=i,window.customElements.define("typo3-extensionmanager-distribution-image",i)}));