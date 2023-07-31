import{directive as r,PartType as e}from"lit-html/directive.js";import{AsyncReplaceDirective as s}from"lit-html/directives/async-replace.js";import{clearPart as t,insertPart as o,setChildPartValue as i}from"lit-html/directive-helpers.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const c=r(class extends s{constructor(r){if(super(r),r.type!==e.CHILD)throw Error("asyncAppend can only be used in child expressions")}update(r,e){return this._$CJ=r,super.update(r,e)}commitValue(r,e){0===e&&t(this._$CJ);const s=o(this._$CJ);i(s,r)}});export{c as asyncAppend};
