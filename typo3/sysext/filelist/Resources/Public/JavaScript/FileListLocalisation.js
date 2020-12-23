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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n,r){"use strict";return new class{constructor(){n.ready().then(()=>{new r("click",(e,t)=>{const n=t.dataset.fileid;document.querySelector('div[data-fileid="'+n+'"]').classList.toggle("hidden")}).delegateTo(document,"a.filelist-translationToggler")})}}}));