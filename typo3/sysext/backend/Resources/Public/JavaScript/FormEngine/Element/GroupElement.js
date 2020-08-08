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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","./AbstractSortableSelectItems","jquery","../../FormEngineSuggest"],(function(e,t,r,s,l){"use strict";s=__importDefault(s);class n extends r.AbstractSortableSelectItems{constructor(e){super(),this.element=null,s.default(()=>{this.element=document.getElementById(e),this.registerEventHandler(),this.registerSuggest()})}registerEventHandler(){this.registerSortableEventHandler(this.element)}registerSuggest(){let e;null!==(e=this.element.closest(".t3js-formengine-field-item").querySelector(".t3-form-suggest"))&&new l(e)}}return n}));