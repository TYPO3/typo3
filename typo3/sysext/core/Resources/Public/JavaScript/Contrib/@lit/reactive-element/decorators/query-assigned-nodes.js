define(["exports","./base"],(function(exports,base){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */exports.queryAssignedNodes=function(o="",n=!1,t=""){return base.decorateProperty({descriptor:e=>({get(){var e,r,l;const i="slot"+(o?`[name=${o}]`:":not([name])");let u=null!==(l=null===(r=null===(e=this.renderRoot)||void 0===e?void 0:e.querySelector(i))||void 0===r?void 0:r.assignedNodes({flatten:n}))&&void 0!==l?l:[];return t&&(u=u.filter(e=>e.nodeType===Node.ELEMENT_NODE&&e.matches(t))),u},enumerable:!0,configurable:!0})})},Object.defineProperty(exports,"__esModule",{value:!0})}));
