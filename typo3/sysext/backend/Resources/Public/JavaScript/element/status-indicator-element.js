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
import{property as p,customElement as d}from"lit/decorators.js";import{LitElement as h,html as f,nothing as c}from"lit";import{classMap as m}from"lit/directives/class-map.js";var l=function(n,t,o,a){var s=arguments.length,e=s<3?t:a===null?a=Object.getOwnPropertyDescriptor(t,o):a,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(n,t,o,a);else for(var u=n.length-1;u>=0;u--)(r=n[u])&&(e=(s<3?r(e):s>3?r(t,o,e):r(t,o))||e);return s>3&&e&&Object.defineProperty(t,o,e),e};const g={online:"live",running:"loading"};let i=class extends h{constructor(){super(...arguments),this.state="default",this.live=!1,this.loading=!1,this.label=null}createRenderRoot(){return this}render(){const t=this.label!==null&&this.label!=="";return f`<span class=${m(this.getClasses())} title=${t?this.label:c} role=${t?"img":c} aria-label=${t?this.label:c} aria-hidden=${t?c:"true"}></span>`}getClasses(){const t=g[this.state];return{"status-indicator":!0,["status-indicator-"+this.state]:!0,"status-indicator-live":this.live||t==="live","status-indicator-loading":this.loading||t==="loading"}}};l([p({type:String})],i.prototype,"state",void 0),l([p({type:Boolean})],i.prototype,"live",void 0),l([p({type:Boolean})],i.prototype,"loading",void 0),l([p({type:String})],i.prototype,"label",void 0),i=l([d("typo3-backend-status-indicator")],i);export{i as StatusIndicatorElement};
