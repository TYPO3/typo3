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
function n(r,t,i){if(typeof r=="function"&&(r=r()!==!1),!r)throw t=t||"Assertion failed",i&&(t=t+" ("+i+")"),typeof Error<"u"?new Error(t):t}class s{constructor(t,i){this.isRunning=!1,this.configuration=t,this.viewModel=i}assert(t,i,o){n(t,i,o)}getPrototypes(){return Array.isArray(this.configuration.selectablePrototypesConfiguration)?this.configuration.selectablePrototypesConfiguration.map(t=>({label:t.label,value:t.identifier})):[]}getTemplatesForPrototype(t){if(n(typeof t=="string",'Invalid parameter "prototypeName"',1475945286),!Array.isArray(this.configuration.selectablePrototypesConfiguration))return[];const i=[];return this.configuration.selectablePrototypesConfiguration.forEach(o=>{Array.isArray(o.newFormTemplates)&&o.identifier===t&&o.newFormTemplates.forEach(a=>{i.push({label:a.label,value:a.templatePath})})}),i}getAccessibleFormStorageFolders(){return Array.isArray(this.configuration.accessibleFormStorageFolders)?this.configuration.accessibleFormStorageFolders.map(t=>({label:t.label,value:t.value})):[]}getAjaxEndpoint(t){return n(typeof this.configuration.endpoints[t]<"u","Endpoint "+t+" does not exist",1477506508),this.configuration.endpoints[t]}run(){if(this.isRunning)throw"You can not run the app twice (1475942618)";return this.bootstrap(),this.isRunning=!0,this}viewSetup(){n(typeof this.viewModel.bootstrap=="function",'The view model does not implement the method "bootstrap"',1475942906),this.viewModel.bootstrap(this)}bootstrap(){this.configuration=this.configuration||{},n(typeof this.configuration.endpoints=="object",'Invalid parameter "endpoints"',1477506504),this.viewSetup()}}let e=null;function u(r,t){return e===null&&(e=new s(r,t)),e}export{s as FormManager,n as assert,u as getInstance};
