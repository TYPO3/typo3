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
define(["require","exports"],(function(e,s){"use strict";Object.defineProperty(s,"__esModule",{value:!0});s.AjaxResponse=class{constructor(e){this.response=e}async resolve(){return this.response.headers.has("Content-Type")&&this.response.headers.get("Content-Type").includes("application/json")?await this.response.json():await this.response.text()}raw(){return this.response}}}));