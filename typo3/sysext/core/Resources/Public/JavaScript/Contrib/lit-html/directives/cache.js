import{render as Q,nothing as D}from"lit-html/lit-html.js";import{directive as e,Directive as i}from"lit-html/directive.js";import{isTemplateResult as nt,getCommittedValue as ut,setCommittedValue as dt,insertPart as at,clearPart as ft,isCompiledTemplateResult as ot}from"lit-html/directive-helpers.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Pt=t=>ot(t)?t._$litType$.h:t.strings,At=e(class extends i{constructor(t){super(t),this.et=new WeakMap}render(t){return[t]}update(t,[i]){const s=nt(this.it)?Pt(this.it):null,e=nt(i)?Pt(i):null;if(null!==s&&(null===e||s!==e)){const i=ut(t).pop();let e=this.et.get(s);if(void 0===e){const t=document.createDocumentFragment();e=Q(D,t),e.setConnected(!1),this.et.set(s,e)}dt(e,[i]),at(e,void 0,i)}if(null!==e){if(null===s||s!==e){const i=this.et.get(e);if(void 0!==i){const s=ut(i).pop();ft(t),at(t,void 0,s),dt(t,[s])}}this.it=i}else this.it=void 0;return this.render(i)}});export{At as cache};
