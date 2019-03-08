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
"use strict";var TYPO3;!function(e){e.Preview=class{constructor(){if(this.dateField=null,this.timeField=null,this.targetField=null,this.updateDateField=()=>{let e=this.dateField.value,t=this.timeField.value;if(!e&&t){let t=new Date;e=t.getFullYear()+"-"+(t.getMonth()+1)+"-"+t.getDate()}if(e&&!t&&(t="00:00"),e||t){const i=new Date(e+" "+t);this.targetField.value=(i.valueOf()/1e3).toString()}else this.targetField.value=""},this.dateField=document.getElementById("preview_simulateDate-date-hr"),this.timeField=document.getElementById("preview_simulateDate-time-hr"),this.targetField=document.getElementById(this.dateField.dataset.target),this.targetField.value){const e=new Date(1e3*parseInt(this.targetField.value,10));this.dateField.valueAsDate=e,this.timeField.valueAsDate=e}this.dateField.addEventListener("change",this.updateDateField),this.timeField.addEventListener("change",this.updateDateField)}}}(TYPO3||(TYPO3={})),window.addEventListener("load",()=>new TYPO3.Preview,!1);