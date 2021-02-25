define(["exports","../lit-html","../directive","../async-directive"],(function(exports,litHtml,directive,asyncDirective){"use strict";
/**
	 * @license
	 * Copyright 2020 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */class o{}const h=new WeakMap,n=directive.directive(class extends asyncDirective.AsyncDirective{render(i){return litHtml.nothing}update(i,[s]){var e;const o=s!==this.gt;return o&&void 0!==this.gt&&this.xt(void 0),(o||this.Tt!==this.Et)&&(this.gt=s,this.At=null===(e=i.options)||void 0===e?void 0:e.host,this.xt(this.Et=i.element)),litHtml.nothing}xt(t){"function"==typeof this.gt?(void 0!==h.get(this.gt)&&this.gt.call(this.At,void 0),h.set(this.gt,t),void 0!==t&&this.gt.call(this.At,t)):this.gt.value=t}get Tt(){var t;return"function"==typeof this.gt?h.get(this.gt):null===(t=this.gt)||void 0===t?void 0:t.value}disconnected(){this.Tt===this.Et&&this.xt(void 0)}reconnected(){this.xt(this.Et)}});exports.createRef=()=>new o,exports.ref=n,Object.defineProperty(exports,"__esModule",{value:!0})}));
