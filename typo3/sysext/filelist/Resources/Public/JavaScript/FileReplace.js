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
define(["require","exports","TYPO3/CMS/Core/Event/RegularEvent","TYPO3/CMS/Core/DocumentService"],(function(e,t,i,n){"use strict";return new class{constructor(){n.ready().then(()=>{this.registerEvents()})}registerEvents(){new i("click",(function(){const e=this.dataset.filelistClickTarget;document.querySelector(e).click()})).delegateTo(document.body,'[data-filelist-click-target]:not([data-filelist-click-target=""]'),new i("change",(function(){const e=this.dataset.filelistChangeTarget;document.querySelector(e).value=this.value})).delegateTo(document.body,'[data-filelist-change-target]:not([data-filelist-change-target=""])')}}}));