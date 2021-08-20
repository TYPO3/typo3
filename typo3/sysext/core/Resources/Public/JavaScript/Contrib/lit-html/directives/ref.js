define(["exports","../lit-html","../directive","../async-directive"],(function(exports,litHtml,directive,asyncDirective){"use strict";
/**
	 * @license
	 * Copyright 2020 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */class o{}const h=new WeakMap,n=directive.directive(class extends asyncDirective.AsyncDirective{render(i){return litHtml.nothing}update(i,[s]){var e;const o=s!==this.q;return o&&void 0!==this.q&&this.nt(void 0),(o||this.ot!==this.rt)&&(this.q=s,this.lt=null===(e=i.options)||void 0===e?void 0:e.host,this.nt(this.rt=i.element)),litHtml.nothing}nt(t){"function"==typeof this.q?(void 0!==h.get(this.q)&&this.q.call(this.lt,void 0),h.set(this.q,t),void 0!==t&&this.q.call(this.lt,t)):this.q.value=t}get ot(){var t;return"function"==typeof this.q?h.get(this.q):null===(t=this.q)||void 0===t?void 0:t.value}disconnected(){this.ot===this.rt&&this.nt(void 0)}reconnected(){this.nt(this.rt)}});exports.createRef=()=>new o,exports.ref=n,Object.defineProperty(exports,"__esModule",{value:!0})}));
