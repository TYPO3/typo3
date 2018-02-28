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
define(["require","exports","../Enum/Severity","../Modal"],function(e,n,t,i){"use strict";return function(){function e(){}return e.wizard=function(e,n){i.advanced({callback:function(e){e.find(".t3js-modal-body").addClass("t3-new-content-element-wizard-window")},content:e,severity:t.SeverityEnum.notice,size:i.sizes.medium,title:n,type:i.types.ajax})},e}()});