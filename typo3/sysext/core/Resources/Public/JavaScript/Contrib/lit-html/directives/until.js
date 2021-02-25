define(["exports","../lit-html","../directive","../directive-helpers","../async-directive"],(function(exports,litHtml,directive,directiveHelpers,asyncDirective){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const e=t=>!directiveHelpers.isPrimitive(t)&&"function"==typeof t.then,o=directive.directive(class extends asyncDirective.AsyncDirective{constructor(){super(...arguments),this.Ct=2147483647,this.Rt=[]}render(...r){var s;return null!==(s=r.find(t=>!e(t)))&&void 0!==s?s:litHtml.noChange}update(r,s){const i=this.Rt;let o=i.length;this.Rt=s;for(let t=0;t<s.length&&!(t>this.Ct);t++){const r=s[t];if(!e(r))return this.Ct=t,r;t<o&&r===i[t]||(this.Ct=2147483647,o=0,Promise.resolve(r).then(t=>{const s=this.Rt.indexOf(r);s>-1&&s<this.Ct&&(this.Ct=s,this.setValue(t))}))}return litHtml.noChange}});exports.until=o,Object.defineProperty(exports,"__esModule",{value:!0})}));
