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
import{html as a}from"lit";import{live as l}from"lit/directives/live.js";import e from"~labels/dashboard.messages";class i{constructor(t){this.context=t,this.key="title",this.title=e.get("dashboard.wizard.title.title"),this.autoAdvance=!1,this.titleValue=""}isComplete(){return this.getValue().trim()!==""}render(){return this.titleValue===""&&(this.titleValue=this.context.getStoreData(this.key)??""),a`<div class=dashboard-wizard-title><div class=form-group><label class=form-label for=dashboard-wizard-title-input>${e.get("dashboard.title")}</label> <input type=text id=dashboard-wizard-title-input class=form-control required .value=${l(this.titleValue)} @input=${t=>this.setValue(t.target.value)}></div></div>`}reset(){this.titleValue="",this.context.clearStoreData(this.key)}getValue(){return this.titleValue}setValue(t){this.titleValue=t,this.context.wizard.requestUpdate()}beforeAdvance(){this.context.setStoreData(this.key,this.getValue())}getSummaryData(){const t=this.context.getStoreData(this.key);return t?[{label:this.title,value:t}]:[]}}export{i as TitleStep,i as default};
