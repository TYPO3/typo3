define(["exports","./base"],(function(exports,base){"use strict";
/**
	 * @license
	 * Copyright 2017 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */exports.queryAsync=function(e){return base.decorateProperty({descriptor:r=>({async get(){var r;return await this.updateComplete,null===(r=this.renderRoot)||void 0===r?void 0:r.querySelector(e)},enumerable:!0,configurable:!0})})},Object.defineProperty(exports,"__esModule",{value:!0})}));
