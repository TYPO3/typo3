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
import{html as c}from"lit";import{property as p,customElement as b}from"lit/decorators.js";import{BaseElement as m}from"@typo3/backend/settings/type/base.js";import{live as v}from"lit/directives/live.js";var d=function(i,e,t,l){var a=arguments.length,o=a<3?e:l===null?l=Object.getOwnPropertyDescriptor(e,t):l,n;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(i,e,t,l);else for(var s=i.length-1;s>=0;s--)(n=i[s])&&(o=(a<3?n(o):a>3?n(e,t,o):n(e,t))||o);return a>3&&o&&Object.defineProperty(e,t,o),o};const u="typo3-backend-settings-type-stringlist";let r=class extends m{updateValue(e,t){const l=[...this.value];l[t]=e,this.value=l}addValue(e,t=""){this.value=this.value.toSpliced(e+1,0,t)}removeValue(e){this.value=this.value.toSpliced(e,1)}renderItem(e,t){return c`<tr><td width=99%><input id=${`${this.formid}${t>0?"-"+t:""}`} type=text class=form-control ?readonly=${this.readonly} .value=${v(e)} @change=${l=>this.updateValue(l.target.value,t)}></td><td><div class=btn-group role=group><button class="btn btn-default" type=button ?disabled=${this.readonly} @click=${()=>this.addValue(t)}><typo3-backend-icon identifier=actions-plus size=small></typo3-backend-icon></button> <button class="btn btn-default" type=button ?disabled=${this.readonly} @click=${()=>this.removeValue(t)}><typo3-backend-icon identifier=actions-delete size=small></typo3-backend-icon></button></div></td></tr>`}render(){const e=this.value||[];return c`<div class=form-control-wrap><div class=table-fit><table class="table table-hover"><tbody>${e.map((t,l)=>this.renderItem(t,l))}</tbody></table></div></div>`}};d([p({type:Array})],r.prototype,"value",void 0),r=d([b(u)],r);export{r as StringlistTypeElement,u as componentName};
