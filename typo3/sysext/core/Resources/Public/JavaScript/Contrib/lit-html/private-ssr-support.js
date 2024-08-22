import{_$LH as X,noChange as R}from"lit-html/lit-html.js";
/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
let Me=null;const Ie={boundAttributeSuffix:X.M,marker:X.P,markerMatch:X.A,HTML_RESULT:X.C,getTemplateHtml:X.L,overrideDirectiveResolve:(e,t)=>class extends e{_$AS(e,r){return t(this,r)}},patchDirectiveResolve:(e,t)=>{if(e.prototype._$AS!==t){Me??=e.prototype._$AS.name;for(let r=e.prototype;r!==Object.prototype;r=Object.getPrototypeOf(r))if(r.hasOwnProperty(Me))return void(r[Me]=t);throw Error("Internal error: It is possible that both dev mode and production mode Lit was mixed together during SSR. Please comment on the issue: https://github.com/lit/lit/issues/4527")}},setDirectiveClass(e,t){e._$litDirective$=t},getAttributePartCommittedValue:(e,t,r)=>{let i=R;return e.j=e=>i=e,e._$AI(t,e,r),i},connectedDisconnectable:e=>({...e,_$AU:!0}),resolveDirective:X.V,AttributePart:X.H,PropertyPart:X.B,BooleanAttributePart:X.N,EventPart:X.U,ElementPart:X.F,TemplateInstance:X.R,isIterable:X.D,ChildPart:X.I};export{Ie as _$LH};
