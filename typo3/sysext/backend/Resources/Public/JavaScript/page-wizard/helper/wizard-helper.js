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
import{topLevelModuleImport as o}from"@typo3/backend/utility/top-level-module-import.js";import{html as a}from"lit";import i,{Size as e}from"@typo3/backend/modal.js";import{SeverityEnum as r}from"@typo3/backend/enum/severity.js";import p from"~labels/backend.layout";const d=async t=>{await o("@typo3/backend/page-wizard/page-wizard.js"),i.advanced({title:p.get("newPage"),content:a`<typo3-backend-page-wizard .configuration=${t}></typo3-backend-page-wizard>`,severity:r.notice,size:{width:e.medium,height:e.large},staticBackdrop:!0,buttons:[]})};export{d as openPageWizardModal};
