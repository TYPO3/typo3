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
define(["require","exports"],function(a,b){"use strict";var c;!function(a){a[a.notice=-2]="notice",a[a.info=-1]="info",a[a.ok=0]="ok",a[a.warning=1]="warning",a[a.error=2]="error"}(c||(c={}));var d,e=function(){function a(){}return a.getCssClass=function(a){var b;switch(a){case c.notice:b="notice";break;case c.ok:b="success";break;case c.warning:b="warning";break;case c.error:b="danger";break;case c.info:default:b="info"}return b},a.notice=c.notice,a.info=c.info,a.ok=c.ok,a.warning=c.warning,a.error=c.error,a}();try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Severity&&(d=window.opener.TYPO3.Severity),parent&&parent.window.TYPO3&&parent.window.TYPO3.Severity&&(d=parent.window.TYPO3.Severity),top&&top.TYPO3&&top.TYPO3.Severity&&(d=top.TYPO3.Severity)}catch(a){}return d||(d=e),TYPO3.Storage=d,d});