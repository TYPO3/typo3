define(["exports","../directive-helpers","../directive","./async-replace"],(function(exports,directiveHelpers,directive,asyncReplace){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const c=directive.directive(class extends asyncReplace.AsyncReplaceDirective{constructor(r){if(super(r),r.type!==directive.PartType.CHILD)throw Error("asyncAppend can only be used in child expressions")}update(r,e){return this._$CX=r,super.update(r,e)}commitValue(r,e){0===e&&directiveHelpers.clearPart(this._$CX);const s=directiveHelpers.insertPart(this._$CX);directiveHelpers.setChildPartValue(s,r)}});exports.asyncAppend=c,Object.defineProperty(exports,"__esModule",{value:!0})}));
