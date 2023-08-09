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
export function assert(t,e,o){if("function"==typeof t&&(t=!1!==t()),!t){if(e=e||"Assertion failed",o&&(e=e+" ("+o+")"),"undefined"!=typeof Error)throw new Error(e);throw e}}export class FormManager{constructor(t,e){this.isRunning=!1,this.configuration=t,this.viewModel=e}assert(t,e,o){assert(t,e,o)}getPrototypes(){return Array.isArray(this.configuration.selectablePrototypesConfiguration)?this.configuration.selectablePrototypesConfiguration.map((t=>({label:t.label,value:t.identifier}))):[]}getTemplatesForPrototype(t){if(assert("string"==typeof t,'Invalid parameter "prototypeName"',1475945286),!Array.isArray(this.configuration.selectablePrototypesConfiguration))return[];const e=[];return this.configuration.selectablePrototypesConfiguration.forEach((o=>{Array.isArray(o.newFormTemplates)&&o.identifier===t&&o.newFormTemplates.forEach((t=>{e.push({label:t.label,value:t.templatePath})}))})),e}getAccessibleFormStorageFolders(){return Array.isArray(this.configuration.accessibleFormStorageFolders)?this.configuration.accessibleFormStorageFolders.map((t=>({label:t.label,value:t.value}))):[]}getAjaxEndpoint(t){return assert(void 0!==this.configuration.endpoints[t],"Endpoint "+t+" does not exist",1477506508),this.configuration.endpoints[t]}run(){if(this.isRunning)throw"You can not run the app twice (1475942618)";return this.bootstrap(),this.isRunning=!0,this}viewSetup(){assert("function"==typeof this.viewModel.bootstrap,'The view model does not implement the method "bootstrap"',1475942906),this.viewModel.bootstrap(this)}bootstrap(){this.configuration=this.configuration||{},assert("object"==typeof this.configuration.endpoints,'Invalid parameter "endpoints"',1477506504),this.viewSetup()}}let formManagerInstance=null;export function getInstance(t,e){return null===formManagerInstance&&(formManagerInstance=new FormManager(t,e)),formManagerInstance}