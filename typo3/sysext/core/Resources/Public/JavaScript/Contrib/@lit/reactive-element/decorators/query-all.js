define(["exports","./base"],(function(exports,base){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */exports.queryAll=function(e){return base.decorateProperty({descriptor:r=>({get(){var r,o;return null!==(o=null===(r=this.renderRoot)||void 0===r?void 0:r.querySelectorAll(e))&&void 0!==o?o:[]},enumerable:!0,configurable:!0})})},Object.defineProperty(exports,"__esModule",{value:!0})}));
