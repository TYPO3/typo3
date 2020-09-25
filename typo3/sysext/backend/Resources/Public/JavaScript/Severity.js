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
define(["require","exports","./Enum/Severity"],(function(e,n,r){"use strict";var i,t=function(){function e(){}return e.getCssClass=function(e){var n;switch(e){case r.SeverityEnum.notice:n="notice";break;case r.SeverityEnum.ok:n="success";break;case r.SeverityEnum.warning:n="warning";break;case r.SeverityEnum.error:n="danger";break;case r.SeverityEnum.info:default:n="info"}return n},e.notice=r.SeverityEnum.notice,e.info=r.SeverityEnum.info,e.ok=r.SeverityEnum.ok,e.warning=r.SeverityEnum.warning,e.error=r.SeverityEnum.error,e}();try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Severity&&(i=window.opener.TYPO3.Severity),parent&&parent.window.TYPO3&&parent.window.TYPO3.Severity&&(i=parent.window.TYPO3.Severity),top&&top.TYPO3&&top.TYPO3.Severity&&(i=top.TYPO3.Severity)}catch(e){}return i||(i=t,"undefined"!=typeof TYPO3&&(TYPO3.Severity=i)),i}));