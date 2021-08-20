define(["exports","../lit-html","../directive"],(function(exports,litHtml,directive){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */class n extends directive.Directive{constructor(i){if(super(i),this.it=litHtml.nothing,i.type!==directive.PartType.CHILD)throw Error(this.constructor.directiveName+"() can only be used in child bindings")}render(r){if(r===litHtml.nothing)return this.vt=void 0,this.it=r;if(r===litHtml.noChange)return r;if("string"!=typeof r)throw Error(this.constructor.directiveName+"() called with a non-string value");if(r===this.it)return this.vt;this.it=r;const s=[r];return s.raw=s,this.vt={_$litType$:this.constructor.resultType,strings:s,values:[]}}}n.directiveName="unsafeHTML",n.resultType=1;const o=directive.directive(n);exports.UnsafeHTMLDirective=n,exports.unsafeHTML=o,Object.defineProperty(exports,"__esModule",{value:!0})}));
