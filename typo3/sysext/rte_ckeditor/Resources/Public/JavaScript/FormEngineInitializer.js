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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],(function(e,i,t,a){"use strict";Object.defineProperty(i,"__esModule",{value:!0}),i.FormEngineInitializer=void 0,t=__importDefault(t);i.FormEngineInitializer=class{static initializeCKEditor(i){e(["ckeditor"],e=>{e.timestamp+="-"+i.configurationHash,i.externalPlugins.forEach(i=>e.plugins.addExternal(i.name,i.resource,"")),(0,t.default)(()=>{const n=i.fieldId,o="#"+t.default.escapeSelector(n);e.replace(n,i.configuration);const d=e.instances[n];d.on("change",e=>{let i=e.sender.commands;d.updateElement(),a.Validation.validateField((0,t.default)(o)),a.Validation.markFieldAsChanged((0,t.default)(o)),void 0!==i.maximize&&1===i.maximize.state&&d.on("maximize",e=>{(0,t.default)(this).off("maximize"),a.Validation.markFieldAsChanged((0,t.default)(o))})}),d.on("mode",e=>{if("source"===e.editor.mode){const e=d.editable();e.attachListener(e,"change",()=>{a.Validation.markFieldAsChanged((0,t.default)(o))})}}),document.addEventListener("inline:sorting-changed",()=>{e.replace(n,i.configuration)}),document.addEventListener("formengine:flexform:sorting-changed",()=>{e.replace(n,i.configuration)})})})}}}));