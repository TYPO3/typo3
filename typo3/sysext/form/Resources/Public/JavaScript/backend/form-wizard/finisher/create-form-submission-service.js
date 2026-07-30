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
import a from"@typo3/core/ajax/ajax-request.js";import r from"~labels/form.form_manager_javascript";class i{constructor(e){this.context=e}async execute(){const e=this.context.getDataStore(),t=e.template;if(!t)throw new Error("No form template selected");const o=this.context.formManager.getAjaxEndpoint("create"),s=await(await new a(o).post({formName:e.settings.formName,templatePath:t.templatePath,prototypeName:t.prototypeIdentifier,storage:e.storage.typeIdentifier,storageLocation:e.settings.storageLocation})).resolve();return s?.status==="success"?{success:!0,finisher:{identifier:"redirect",module:"@typo3/backend/wizard/finisher/redirect-finisher.js",data:{url:s.url},labels:{successTitle:r.get("formManager.finisher.redirect.success.title"),successDescription:r.get("formManager.finisher.redirect.success.description")}}}:{success:!1,errors:[s?.message]}}}export{i as CreateFormSubmissionService};
