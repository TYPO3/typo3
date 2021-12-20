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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","TYPO3/CMS/RteCkeditor/CKEditorLoader","jquery","TYPO3/CMS/Backend/FormEngine"],(function(e,i,t,a,n){"use strict";Object.defineProperty(i,"__esModule",{value:!0}),i.FormEngineInitializer=void 0,a=__importDefault(a);i.FormEngineInitializer=class{static initializeCKEditor(e){t.loadCKEditor().then(i=>{i.timestamp+="-"+e.configurationHash,e.externalPlugins.forEach(e=>i.plugins.addExternal(e.name,e.resource,"")),a.default(()=>{const t=e.fieldId,o="#"+a.default.escapeSelector(t);i.replace(t,e.configuration);const d=i.instances[t];d.on("change",e=>{let i=e.sender.commands;d.updateElement(),n.Validation.validateField(a.default(o)),n.Validation.markFieldAsChanged(a.default(o)),void 0!==i.maximize&&1===i.maximize.state&&d.on("maximize",e=>{a.default(this).off("maximize"),n.Validation.markFieldAsChanged(a.default(o))})}),d.on("mode",e=>{if("source"===e.editor.mode){const e=d.editable();e.attachListener(e,"change",()=>{n.Validation.markFieldAsChanged(a.default(o))})}}),document.addEventListener("inline:sorting-changed",()=>{d.destroy(),i.replace(t,e.configuration)}),document.addEventListener("formengine:flexform:sorting-changed",()=>{d.destroy(),i.replace(t,e.configuration)})})})}}}));