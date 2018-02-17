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
define(["require","exports","TYPO3/CMS/Backend/Modal","../Severity"],function(a,b,c,d){"use strict";var e=function(){function a(){}return a.wizard=function(a,b){c.advanced({callback:function(a){a.find(".t3js-modal-body").addClass("t3-new-content-element-wizard-window")},content:a,severity:d.notice,size:c.sizes.medium,title:b,type:c.types.ajax})},a}();return e});