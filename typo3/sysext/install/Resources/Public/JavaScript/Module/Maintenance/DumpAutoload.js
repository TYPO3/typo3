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
define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../AbstractInlineModule"],(function(e,t,s,n,o,a){"use strict";class r extends a.AbstractInlineModule{initialize(e){this.setButtonState(e,!1),new n(o.getUrl("dumpAutoload")).get({cache:"no-cache"}).then(async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?t.status.length>0&&t.status.forEach(e=>{s.success(e.message)}):s.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},()=>{s.error("Autoloader not dumped","Dumping autoload files failed for unknown reasons. Check the system for broken extensions and try again.")}).finally(()=>{this.setButtonState(e,!0)})}}return new r}));