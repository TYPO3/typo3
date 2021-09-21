define(["exports","./lit-html"],(function(exports,litHtml){"use strict";
/**
	 * @license
	 * Copyright 2019 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const r={boundAttributeSuffix:litHtml._$LH.P,marker:litHtml._$LH.V,markerMatch:litHtml._$LH.L,HTML_RESULT:litHtml._$LH.I,getTemplateHtml:litHtml._$LH.N,overrideDirectiveResolve:(t,e)=>class extends t{_$AS(t,r){return e(this,r)}},setDirectiveClass(t,e){t._$litDirective$=e},getAttributePartCommittedValue:(t,r,i)=>{let a=litHtml.noChange;return t.k=t=>a=t,t._$AI(r,t,i),a},connectedDisconnectable:t=>({...t,_$AU:!0}),resolveDirective:litHtml._$LH.j,AttributePart:litHtml._$LH.O,PropertyPart:litHtml._$LH.W,BooleanAttributePart:litHtml._$LH.F,EventPart:litHtml._$LH.B,ElementPart:litHtml._$LH.Z};exports._$LH=r,Object.defineProperty(exports,"__esModule",{value:!0})}));
