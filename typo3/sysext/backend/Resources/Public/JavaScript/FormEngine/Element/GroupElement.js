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
define(["require","exports","./AbstractSortableSelectItems","TYPO3/CMS/Core/DocumentService","../../FormEngineSuggest"],(function(e,t,r,s,n){"use strict";class l extends r.AbstractSortableSelectItems{constructor(e){super(),this.element=null,s.ready().then(()=>{this.element=document.getElementById(e),this.registerEventHandler(),this.registerSuggest()})}registerEventHandler(){this.registerSortableEventHandler(this.element)}registerSuggest(){let e;null!==(e=this.element.closest(".t3js-formengine-field-item").querySelector(".t3-form-suggest"))&&new n(e)}}return l}));