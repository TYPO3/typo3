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
import{property as o}from"lit/decorators.js";import{LitElement as b,css as u,html as h}from"lit";import{KeyTypesEnum as p}from"@typo3/backend/enum/key-types.js";var f=function(s,t,i,r){var a=arguments.length,e=a<3?t:r===null?r=Object.getOwnPropertyDescriptor(t,i):r,l;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")e=Reflect.decorate(s,t,i,r);else for(var d=s.length-1;d>=0;d--)(l=s[d])&&(e=(a<3?l(e):a>3?l(t,i,e):l(t,i))||e);return a>3&&e&&Object.defineProperty(t,i,e),e};class n extends b{static{this.styles=[u`:host{cursor:pointer;appearance:button}`,u`:host([disabled]){cursor:default}`]}constructor(){super(),this.role="button",this.tabIndex=0,this.disabled=!1,this.enabledTabIndex=0,this.addEventListener("click",t=>{t.preventDefault(),this.activate(t)}),this.addEventListener("keydown",t=>{t.key===p.SPACE&&t.preventDefault(),t.key===p.ENTER&&(t.preventDefault(),this.activate(t))}),this.addEventListener("keyup",t=>{t.key===p.SPACE&&(t.preventDefault(),this.activate(t))})}willUpdate(t){super.willUpdate(t),t.has("disabled")&&this.disabled!==!!t.get("disabled")&&(this.disabled&&(this.enabledTabIndex=this.tabIndex),this.ariaDisabled=this.disabled?"true":null,this.tabIndex=this.disabled?-1:this.enabledTabIndex)}render(){return h`<slot></slot>`}activate(t){this.disabled||this.buttonActivated(t)}}f([o({type:String,reflect:!0})],n.prototype,"role",void 0),f([o({type:String,reflect:!0})],n.prototype,"tabIndex",void 0),f([o({type:Boolean,reflect:!0})],n.prototype,"disabled",void 0);export{n as PseudoButtonLitElement};
