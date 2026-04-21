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
import{html as d}from"lit";import{property as p,customElement as b}from"lit/decorators.js";import{BaseElement as m}from"@typo3/backend/settings/type/base.js";import{live as f}from"lit/directives/live.js";var c=function(i,t,e,l){var n=arguments.length,o=n<3?t:l===null?l=Object.getOwnPropertyDescriptor(t,e):l,a;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(i,t,e,l);else for(var s=i.length-1;s>=0;s--)(a=i[s])&&(o=(n<3?a(o):n>3?a(t,e,o):a(t,e))||o);return n>3&&o&&Object.defineProperty(t,e,o),o};const u="typo3-backend-settings-type-stringlist";let r=class extends m{updateValue(t,e){const l=[...this.value];l[e]=t,this.value=l}addValue(t,e=""){this.value=this.value.toSpliced(t+1,0,e)}removeValue(t){this.value=this.value.toSpliced(t,1)}renderItem(t,e){return d`<tr><td width=99%><input id=${`${this.formid}${e>0?"-"+e:""}`} type=text class=form-control ?readonly=${this.readonly} .value=${f(t)} @change=${l=>this.updateValue(l.target.value,e)}></td><td><div class=btn-group role=group><button class="btn btn-default" type=button ?disabled=${this.readonly} @click=${()=>this.addValue(e)}><typo3-backend-icon identifier=actions-plus size=small></typo3-backend-icon></button> <button class="btn btn-default" type=button ?disabled=${this.readonly} @click=${()=>this.removeValue(e)}><typo3-backend-icon identifier=actions-delete size=small></typo3-backend-icon></button></div></td></tr>`}render(){const t=this.value||[];return t.length===0?d`<button id=${this.formid} class="btn btn-default" type=button ?disabled=${this.readonly} @click=${()=>this.addValue(0)}><typo3-backend-icon identifier=actions-plus size=small></typo3-backend-icon></button>`:d`<div class=form-control-wrap><div class=table-fit><table class="table table-hover"><tbody>${t.map((e,l)=>this.renderItem(e,l))}</tbody></table></div></div>`}};c([p({type:Array})],r.prototype,"value",void 0),r=c([b(u)],r);export{r as StringlistTypeElement,u as componentName};
