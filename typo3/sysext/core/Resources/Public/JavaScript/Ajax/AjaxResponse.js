/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
define(["require","exports"],(function(e,s){"use strict";Object.defineProperty(s,"__esModule",{value:!0}),s.AjaxResponse=void 0;s.AjaxResponse=class{constructor(e){this.response=e}async resolve(e){var s;if(void 0!==this.resolvedBody)return this.resolvedBody;const t=null!==(s=this.response.headers.get("Content-Type"))&&void 0!==s?s:"";return"json"===e||t.startsWith("application/json")?this.resolvedBody=await this.response.json():this.resolvedBody=await this.response.text(),this.resolvedBody}raw(){return this.response}async dereference(){const e=new Map;return this.response.headers.forEach((s,t)=>e.set(t,s)),{status:this.response.status,headers:e,body:await this.resolve()}}}}));