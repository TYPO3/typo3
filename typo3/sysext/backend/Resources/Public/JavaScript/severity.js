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
import{SeverityEnum as e}from"@typo3/backend/enum/severity.js";class r{static{this.notice=e.notice}static{this.info=e.info}static{this.ok=e.ok}static{this.warning=e.warning}static{this.error=e.error}static getCssClass(n){let t;switch(n){case e.notice:t="notice";break;case e.ok:t="success";break;case e.warning:t="warning";break;case e.error:t="danger";break;case e.info:default:t="info"}return t}}let i;try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Severity&&(i=window.opener.TYPO3.Severity),parent&&parent.window.TYPO3&&parent.window.TYPO3.Severity&&(i=parent.window.TYPO3.Severity),top&&top.TYPO3&&top.TYPO3.Severity&&(i=top.TYPO3.Severity)}catch{}i||(i=r,typeof TYPO3<"u"&&(TYPO3.Severity=i));export{r as default};
