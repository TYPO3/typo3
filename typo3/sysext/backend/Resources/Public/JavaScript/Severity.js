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
define(["require","exports","./Enum/Severity"],(function(e,r,n){"use strict";class t{static getCssClass(e){let r;switch(e){case n.SeverityEnum.notice:r="notice";break;case n.SeverityEnum.ok:r="success";break;case n.SeverityEnum.warning:r="warning";break;case n.SeverityEnum.error:r="danger";break;case n.SeverityEnum.info:default:r="info"}return r}}let i;t.notice=n.SeverityEnum.notice,t.info=n.SeverityEnum.info,t.ok=n.SeverityEnum.ok,t.warning=n.SeverityEnum.warning,t.error=n.SeverityEnum.error;try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Severity&&(i=window.opener.TYPO3.Severity),parent&&parent.window.TYPO3&&parent.window.TYPO3.Severity&&(i=parent.window.TYPO3.Severity),top&&top.TYPO3&&top.TYPO3.Severity&&(i=top.TYPO3.Severity)}catch(e){}return i||(i=t,"undefined"!=typeof TYPO3&&(TYPO3.Severity=i)),i}));