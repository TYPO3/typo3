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
define(["require","exports","./Enum/Severity"],function(a,b,c){"use strict";var d,e=function(){function a(){}return a.getCssClass=function(a){var b;switch(a){case c.SeverityEnum.notice:b="notice";break;case c.SeverityEnum.ok:b="success";break;case c.SeverityEnum.warning:b="warning";break;case c.SeverityEnum.error:b="danger";break;case c.SeverityEnum.info:default:b="info"}return b},a.notice=c.SeverityEnum.notice,a.info=c.SeverityEnum.info,a.ok=c.SeverityEnum.ok,a.warning=c.SeverityEnum.warning,a.error=c.SeverityEnum.error,a}();try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Severity&&(d=window.opener.TYPO3.Severity),parent&&parent.window.TYPO3&&parent.window.TYPO3.Severity&&(d=parent.window.TYPO3.Severity),top&&top.TYPO3&&top.TYPO3.Severity&&(d=top.TYPO3.Severity)}catch(a){}return d||(d=e,"undefined"!=typeof TYPO3&&(TYPO3.Severity=d)),d});