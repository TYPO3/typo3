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
import{SeverityEnum}from"@typo3/backend/enum/severity.js";class Severity{static getCssClass(e){let t;switch(e){case SeverityEnum.notice:t="notice";break;case SeverityEnum.ok:t="success";break;case SeverityEnum.warning:t="warning";break;case SeverityEnum.error:t="danger";break;case SeverityEnum.info:default:t="info"}return t}}Severity.notice=SeverityEnum.notice,Severity.info=SeverityEnum.info,Severity.ok=SeverityEnum.ok,Severity.warning=SeverityEnum.warning,Severity.error=SeverityEnum.error;export default Severity;let severityObject;try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Severity&&(severityObject=window.opener.TYPO3.Severity),parent&&parent.window.TYPO3&&parent.window.TYPO3.Severity&&(severityObject=parent.window.TYPO3.Severity),top&&top.TYPO3&&top.TYPO3.Severity&&(severityObject=top.TYPO3.Severity)}catch{}severityObject||(severityObject=Severity,"undefined"!=typeof TYPO3&&(TYPO3.Severity=severityObject));