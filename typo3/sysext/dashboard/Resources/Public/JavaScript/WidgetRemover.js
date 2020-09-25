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
define(["require","exports","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Enum/Severity","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,i,n,a){"use strict";return new class{constructor(){this.selector=".js-dashboard-remove-widget",this.initialize()}initialize(){new a("click",(function(e){e.preventDefault();i.confirm(this.dataset.modalTitle,this.dataset.modalQuestion,n.SeverityEnum.warning,[{text:this.dataset.modalCancel,active:!0,btnClass:"btn-default",name:"cancel"},{text:this.dataset.modalOk,btnClass:"btn-warning",name:"delete"}]).on("button.clicked",e=>{"delete"===e.target.getAttribute("name")&&(window.location.href=this.getAttribute("href")),i.dismiss()})})).delegateTo(document,this.selector)}}}));