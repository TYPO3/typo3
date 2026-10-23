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
import o from"@typo3/core/ajax/ajax-request.js";import s from"~labels/form.form_manager_javascript";class a{constructor(e){this.context=e}async execute(){const e=this.context.getDataStore(),r=this.context.formManager.getAjaxEndpoint("create"),t=await(await new o(r).post({formName:e.settings.formName,templatePath:e.settings.template,prototypeName:e.settings.prototype,storage:e.storage.typeIdentifier,storageLocation:e.settings.storageLocation})).resolve();return t?.status==="success"?{success:!0,finisher:{identifier:"redirect",module:"@typo3/backend/wizard/finisher/redirect-finisher.js",data:{url:t.url},labels:{successTitle:s.get("formManager.finisher.redirect.success.title"),successDescription:s.get("formManager.finisher.redirect.success.description")}}}:{success:!1,errors:[t?.message??""]}}}export{a as CreateFormSubmissionService};
