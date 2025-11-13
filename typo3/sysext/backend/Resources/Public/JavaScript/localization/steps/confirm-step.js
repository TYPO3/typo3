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
import{html as o}from"lit";import{lll as i}from"@typo3/core/lit-helper.js";class s{constructor(e){this.context=e,this.key="confirm",this.title=i("step.confirmation.title"),this.autoAdvance=!1}isComplete(){return!0}render(){const e=this.context.wizard.getStepSummaries(),t=this.context.wizard.getRecordInfo();return o`<div class=localization-confirmation><h2 class=h4>${i("step.confirmation.headline")}</h2><p>${i("step.confirmation.description")}</p><div class="table-fit table-fit-wrap"><table class="table table-striped"><tbody>${t?o`<tr><th class=col-fieldname>${t.typeName}</th><td class=col-word-break><typo3-backend-icon identifier=${t.icon} size=small class=me-1></typo3-backend-icon>${t.title} <code>[${t.type}:${t.uid}]</code></td></tr>`:""} ${e}</tbody></table></div></div>`}}export{s as ConfirmStep,s as default};
