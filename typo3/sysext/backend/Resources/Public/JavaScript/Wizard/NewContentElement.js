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
define(["require","exports","../Enum/Severity","../Modal","TYPO3/CMS/Backend/NewContentElementWizard"],(function(e,t,n,a,d){"use strict";return class{static wizard(e,t){const i=a.advanced({callback:e=>{e.find(".t3js-modal-body").addClass("t3-new-content-element-wizard-window")},content:e,severity:n.SeverityEnum.notice,size:a.sizes.medium,title:t,type:a.types.ajax}).on("modal-loaded",()=>{new d.default(i)})}}}));