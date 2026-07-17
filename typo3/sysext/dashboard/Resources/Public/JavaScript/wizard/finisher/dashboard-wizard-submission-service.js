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
import{DashboardAddEvent as r}from"@typo3/dashboard/dashboard.js";import s from"~labels/dashboard.messages";class i{constructor(e){this.context=e}async execute(){const{preset:e,title:t}=this.context.getDataStore();return{success:!0,finisher:{identifier:"event",module:"@typo3/backend/wizard/finisher/event-finisher.js",data:{event:new r(e??"",t??"")},labels:{successTitle:s.get("dashboard.wizard.success.title"),successDescription:s.get("dashboard.wizard.success.message")}}}}}export{i as DashboardWizardSubmissionService};
