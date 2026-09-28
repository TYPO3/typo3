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
import e from"@typo3/core/ajax/ajax-request.js";class r{constructor(t){this.context=t}async execute(){return await(await new e(TYPO3.settings.ajaxUrls.wizard_localization_localize).post({recordType:this.context.recordType,recordUid:this.context.recordUid,...this.context.returnUrl?{returnUrl:this.context.returnUrl}:{},data:this.context.getDataStore()})).resolve()}}export{r as LocalizationSubmissionService};
