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
function n(i,t,e){if(typeof i=="function"&&(i=i()!==!1),!i)throw t=t||"Assertion failed",e&&(t=t+" ("+e+")"),typeof Error<"u"?new Error(t):t}class a{constructor(t,e){this.isRunning=!1,this.configuration=t,this.viewModel=e}assert(t,e,r){n(t,e,r)}getPrototypes(){return Array.isArray(this.configuration.selectablePrototypesConfiguration)?this.configuration.selectablePrototypesConfiguration.map(t=>({label:t.label,value:t.identifier})):[]}getTemplatesGroupedByPrototype(){return Array.isArray(this.configuration.selectablePrototypesConfiguration)?this.configuration.selectablePrototypesConfiguration.filter(t=>Array.isArray(t.newFormTemplates)&&t.newFormTemplates.length>0).map(t=>({label:t.label,identifier:t.identifier,templates:t.newFormTemplates.map(e=>({label:e.label,description:e.description,templatePath:e.templatePath,prototypeIdentifier:t.identifier,iconIdentifier:e.iconIdentifier??"form-page"}))})):[]}getAccessibleStorageAdapters(){return Array.isArray(this.configuration.accessibleStorageAdapters)?this.configuration.accessibleStorageAdapters:[]}getAccessibleStorageLocationsForAdapter(t){const e=this.configuration.accessibleStorageAdapters?.find(r=>r.typeIdentifier===t.typeIdentifier);return!e||!e.options?.allowedStorageLocations?[]:e.options.allowedStorageLocations}getAjaxEndpoint(t){return n(typeof this.configuration.endpoints[t]<"u","Endpoint "+t+" does not exist",1477506508),this.configuration.endpoints[t]}run(){if(this.isRunning)throw"You can not run the app twice (1475942618)";return this.bootstrap(),this.isRunning=!0,this}viewSetup(){n(typeof this.viewModel.bootstrap=="function",'The view model does not implement the method "bootstrap"',1475942906),this.viewModel.bootstrap(this)}bootstrap(){this.configuration=this.configuration||{},n(typeof this.configuration.endpoints=="object",'Invalid parameter "endpoints"',1477506504),this.viewSetup()}}let o=null;function s(i,t){return o===null&&(o=new a(i,t)),o}export{a as FormManager,n as assert,s as getInstance};
