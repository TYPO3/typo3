define(["exports","../lit-html","../directive-helpers","../directive"],(function(exports,litHtml,directiveHelpers,directive){"use strict";
/**
	 * @license
	 * Copyright 2020 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const l=directive.directive(class extends directive.Directive{constructor(r){if(super(r),r.type!==directive.PartType.PROPERTY&&r.type!==directive.PartType.ATTRIBUTE&&r.type!==directive.PartType.BOOLEAN_ATTRIBUTE)throw Error("The `live` directive is not allowed on child or event bindings");if(!directiveHelpers.isSingleExpression(r))throw Error("`live` bindings can only contain a single expression")}render(r){return r}update(i,[t]){if(t===litHtml.noChange||t===litHtml.nothing)return t;const o=i.element,l=i.name;if(i.type===directive.PartType.PROPERTY){if(t===o[l])return litHtml.noChange}else if(i.type===directive.PartType.BOOLEAN_ATTRIBUTE){if(!!t===o.hasAttribute(l))return litHtml.noChange}else if(i.type===directive.PartType.ATTRIBUTE&&o.getAttribute(l)===t+"")return litHtml.noChange;return directiveHelpers.setCommittedValue(i),t}});exports.live=l,Object.defineProperty(exports,"__esModule",{value:!0})}));
