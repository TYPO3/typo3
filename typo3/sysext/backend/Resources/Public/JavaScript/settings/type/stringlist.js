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
var __decorate=function(t,e,l,i){var o,r=arguments.length,n=r<3?e:null===i?i=Object.getOwnPropertyDescriptor(e,l):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(t,e,l,i);else for(var a=t.length-1;a>=0;a--)(o=t[a])&&(n=(r<3?o(n):r>3?o(e,l,n):o(e,l))||n);return r>3&&n&&Object.defineProperty(e,l,n),n};import{html}from"lit";import{customElement,property}from"lit/decorators.js";import{BaseElement}from"@typo3/backend/settings/type/base.js";import{live}from"lit/directives/live.js";export const componentName="typo3-backend-settings-type-stringlist";let StringlistTypeElement=class extends BaseElement{updateValue(t,e){const l=[...this.value];l[e]=t,this.value=l}addValue(t,e=""){this.value=this.value.toSpliced(t+1,0,e)}removeValue(t){this.value=this.value.toSpliced(t,1)}renderItem(t,e){return html`
      <tr>
        <td width="99%">
          <input
            id=${`${this.formid}${e>0?"-"+e:""}`}
            type="text"
            class="form-control"
            ?readonly=${this.readonly}
            .value=${live(t)}
            @change=${t=>this.updateValue(t.target.value,e)}
          />
        </td>
        <td>
          <div class="btn-group" role="group">
            <button class="btn btn-default" type="button" ?disabled=${this.readonly} @click=${()=>this.addValue(e)}>
              <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
            </button>
            <button class="btn btn-default" type="button" ?disabled=${this.readonly} @click=${()=>this.removeValue(e)}>
              <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
            </button>
          </div>
        </td>
      </tr>
    `}render(){const t=this.value||[];return html`
      <div class="form-control-wrap">
        <div class="table-fit">
          <table class="table table-hover">
            <tbody>
              ${t.map(((t,e)=>this.renderItem(t,e)))}
            </tbody>
          </table>
        </div>
      </div>
    `}};__decorate([property({type:Array})],StringlistTypeElement.prototype,"value",void 0),StringlistTypeElement=__decorate([customElement(componentName)],StringlistTypeElement);export{StringlistTypeElement};