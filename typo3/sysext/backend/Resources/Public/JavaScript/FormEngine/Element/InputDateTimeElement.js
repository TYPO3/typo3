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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine","TYPO3/CMS/Backend/FormEngineValidation"],(function(e,t,i,r,a){"use strict";i=__importDefault(i);return class{constructor(t){i.default(()=>{this.registerEventHandler(),e(["../../DateTimePicker"],e=>{e.initialize("#"+t)})})}registerEventHandler(){i.default(document).on("formengine.dp.change",(e,t)=>{a.validateField(t),r.Validation.markFieldAsChanged(t),i.default(".module-docheader-bar .btn").removeClass("disabled").prop("disabled",!1)})}}}));