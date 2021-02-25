define(["exports","../lit-html","../directive","../directive-helpers","../async-directive"],(function(exports,litHtml,directive,directiveHelpers,asyncDirective){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const c=directive.directive(class extends asyncDirective.AsyncDirective{constructor(t){if(super(t),t.type!==directive.PartType.CHILD)throw Error("asyncAppend can only be used in child expressions")}render(e,i){return litHtml.noChange}update(e,[i,s]){if(i!==this.vt)return this.vt=i,this.Σft(e,s),litHtml.noChange}async Σft(t,e){let i=0;const{vt:n}=this;for await(let c of n){if(this.vt!==n)break;this.wt&&await this.wt,0===i&&directiveHelpers.clearPart(t),void 0!==e&&(c=e(c,i));const h=directiveHelpers.insertPart(t);directiveHelpers.setChildPartValue(h,c),i++}}disconnected(){this.wt=new Promise(t=>this.yt=t)}reconnected(){this.wt=void 0,this.yt()}});exports.asyncAppend=c,Object.defineProperty(exports,"__esModule",{value:!0})}));
