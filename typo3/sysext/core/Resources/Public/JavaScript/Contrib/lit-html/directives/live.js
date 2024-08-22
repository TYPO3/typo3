import{noChange as R,nothing as D}from"lit-html/lit-html.js";import{directive as e,Directive as i,PartType as t}from"lit-html/directive.js";import{isSingleExpression as rt,setCommittedValue as dt}from"lit-html/directive-helpers.js";
/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Ft=e(class extends i{constructor(r){if(super(r),r.type!==t.PROPERTY&&r.type!==t.ATTRIBUTE&&r.type!==t.BOOLEAN_ATTRIBUTE)throw Error("The `live` directive is not allowed on child or event bindings");if(!rt(r))throw Error("`live` bindings can only contain a single expression")}render(r){return r}update(r,[e]){if(e===R||e===D)return e;const i=r.element,n=r.name;if(r.type===t.PROPERTY){if(e===i[n])return R}else if(r.type===t.BOOLEAN_ATTRIBUTE){if(!!e===i.hasAttribute(n))return R}else if(r.type===t.ATTRIBUTE&&i.getAttribute(n)===e+"")return R;return dt(r),e}});export{Ft as live};
