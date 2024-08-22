import{nothing as D,noChange as R}from"lit-html/lit-html.js";import{Directive as i,PartType as t,directive as e}from"lit-html/directive.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */class le extends i{constructor(i){if(super(i),this.it=D,i.type!==t.CHILD)throw Error(this.constructor.directiveName+"() can only be used in child bindings")}render(t){if(t===D||null==t)return this._t=void 0,this.it=t;if(t===R)return t;if("string"!=typeof t)throw Error(this.constructor.directiveName+"() called with a non-string value");if(t===this.it)return this._t;this.it=t;const i=[t];return i.raw=i,this._t={_$litType$:this.constructor.resultType,strings:i,values:[]}}}le.directiveName="unsafeHTML",le.resultType=1;const ae=e(le);export{le as UnsafeHTMLDirective,ae as unsafeHTML};
