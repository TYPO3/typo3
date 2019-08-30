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
define(["require","exports","../Enum/Severity","../Modal"],function(e,t,i,n){"use strict";return class{static wizard(e,t){n.advanced({callback:e=>{e.find(".t3js-modal-body").addClass("t3-new-content-element-wizard-window")},content:e,severity:i.SeverityEnum.notice,size:n.sizes.medium,title:t,type:n.types.ajax})}}});