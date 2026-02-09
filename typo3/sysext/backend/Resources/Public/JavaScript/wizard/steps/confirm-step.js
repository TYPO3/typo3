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
import{html as i}from"lit";import{StepSummaryEvent as o}from"@typo3/backend/wizard/events/step-summary-event.js";import e from"~labels/backend.wizards.general";class r{constructor(t){this.wizard=t,this.key="confirm",this.title=e.get("step.confirmation.title"),this.autoAdvance=!1}isComplete(){return!0}render(){const t=new o(this.wizard.getStepSummaries());this.wizard.dispatchEvent(t);const s=t.detail.summaryData;return i`<div class=localization-confirmation><h2 class=h4>${e.get("step.confirmation.headline")}</h2><p>${e.get("step.confirmation.description")}</p><div class="table-fit table-fit-wrap"><table class="table table-striped"><tbody>${s.map(a=>i`<tr><th class=col-fieldname>${a.label}</th><td class=col-word-break>${a.value}</td></tr>`)}</tbody></table></div></div>`}}export{r as ConfirmStep,r as default};
