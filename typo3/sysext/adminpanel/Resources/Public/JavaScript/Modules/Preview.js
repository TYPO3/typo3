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
"use strict";var TYPO3;!function(e){var t=function(){return function(){var e=this;if(this.dateField=null,this.timeField=null,this.targetField=null,this.updateDateField=function(){var t=e.dateField.value,i=e.timeField.value;if(!t&&i){var a=new Date;t=a.getFullYear()+"-"+(a.getMonth()+1)+"-"+a.getDate()}if(t&&!i&&(i="00:00"),t||i){var d=new Date(t+" "+i);e.targetField.value=d.toISOString()}else e.targetField.value=""},this.dateField=document.getElementById("preview_simulateDate-date-hr"),this.timeField=document.getElementById("preview_simulateDate-time-hr"),this.targetField=document.getElementById(this.dateField.dataset.target),this.targetField.value){var t=new Date(this.targetField.value);this.dateField.value=t.getFullYear()+"-"+(t.getMonth()+1<10?"0":"")+(t.getMonth()+1)+"-"+(t.getDate()<10?"0":"")+t.getDate(),this.timeField.value=(t.getHours()<10?"0":"")+t.getHours()+":"+(t.getMinutes()<10?"0":"")+t.getMinutes()}this.dateField.addEventListener("change",this.updateDateField),this.timeField.addEventListener("change",this.updateDateField)}}();e.Preview=t}(TYPO3||(TYPO3={})),window.addEventListener("load",function(){return new TYPO3.Preview},!1);