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
(()=>{class l{constructor(){if(this.dateField=null,this.timeField=null,this.targetField=null,this.toggleField=null,this.toggleDisplay=()=>{const e=this.toggleField.checked,t=document.getElementById("typo3-adminPanel-preview_simulateDate");e?(t.classList.remove("typo3-adminPanel-group-disable"),this.dateField.disabled=!1,this.timeField.disabled=!1,this.updateDateField()):(t.classList.add("typo3-adminPanel-group-disable"),this.dateField.disabled=!0,this.timeField.disabled=!0,this.targetField.value="")},this.updateDateField=()=>{let e=this.dateField.value,t=this.timeField.value;if(!e&&t){const i=new Date;e=i.getFullYear()+"-"+(i.getMonth()+1)+"-"+i.getDate()}if(e&&!t&&(t="00:00"),!e&&!t)this.targetField.value="";else{const i=e+" "+t,a=new Date(i);this.targetField.value=(a.valueOf()/1e3).toString()}},this.dateField=document.getElementById("preview_simulateDate-date-hr"),this.timeField=document.getElementById("preview_simulateDate-time-hr"),this.targetField=document.getElementById(this.dateField.dataset.bsTarget),this.toggleField=document.getElementById("typo3-adminPanel-simulate-date-toggle"),this.targetField.value){const e=new Date(parseInt(this.targetField.value,10)*1e3);this.dateField.valueAsDate=e,this.timeField.valueAsDate=e}this.toggleField.addEventListener("change",this.toggleDisplay),this.dateField.addEventListener("change",this.updateDateField),this.timeField.addEventListener("change",this.updateDateField)}}window.addEventListener("load",()=>new l,!1)})();
