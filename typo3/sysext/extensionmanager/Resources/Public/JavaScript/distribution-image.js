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
var __decorate=function(t,e,i,r){var o,a=arguments.length,s=a<3?e:null===r?r=Object.getOwnPropertyDescriptor(e,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(t,e,i,r);else for(var n=t.length-1;n>=0;n--)(o=t[n])&&(s=(a<3?o(s):a>3?o(e,i,s):o(e,i))||s);return a>3&&s&&Object.defineProperty(e,i,s),s};import{css,html,LitElement,nothing}from"lit";import{customElement,property}from"lit/decorators.js";let DistributionImage=class extends LitElement{render(){if(!this.image&&!this.fallback)return nothing;const t=this.welcomeImage||this.image||this.fallback;return html`<img alt="${this.alt}" src="${t}" @error="${t!==this.fallback?this.onError:nothing}">`}onError(t){const e=t.target;this.image.length&&e.getAttribute("src")===this.welcomeImage?e.setAttribute("src",this.image):this.fallback.length&&e.setAttribute("src",this.fallback)}};DistributionImage.styles=css`
    img {
      display: block;
      width: 100%;
      height: auto;
    }
  `,__decorate([property({type:String})],DistributionImage.prototype,"alt",void 0),__decorate([property({type:String})],DistributionImage.prototype,"image",void 0),__decorate([property({type:String})],DistributionImage.prototype,"welcomeImage",void 0),__decorate([property({type:String})],DistributionImage.prototype,"fallback",void 0),DistributionImage=__decorate([customElement("typo3-extensionmanager-distribution-image")],DistributionImage);export{DistributionImage};