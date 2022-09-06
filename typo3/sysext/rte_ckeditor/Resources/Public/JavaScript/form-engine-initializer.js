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
import{loadCKEditor}from"@typo3/rte-ckeditor/ckeditor-loader.js";import DocumentService from"@typo3/core/document-service.js";import FormEngine from"@typo3/backend/form-engine.js";export class FormEngineInitializer{static initializeCKEditor(e){loadCKEditor().then((i=>{i.timestamp+="-"+e.configurationHash,e.externalPlugins.forEach((e=>i.plugins.addExternal(e.name,e.resource,""))),DocumentService.ready().then((()=>{const n=e.fieldId,o=document.getElementById(n);i.replace(n,e.configuration);const t=i.instances[n];t.on("change",(e=>{let i=e.sender.commands;t.updateElement(),FormEngine.Validation.validateField(o),FormEngine.Validation.markFieldAsChanged(o),void 0!==i.maximize&&1===i.maximize.state&&t.once("maximize",(e=>{FormEngine.Validation.markFieldAsChanged(o)}))})),t.on("mode",(e=>{if("source"===e.editor.mode){const e=t.editable();e.attachListener(e,"change",(()=>{FormEngine.Validation.markFieldAsChanged(o)}))}})),["inline:sorting-changed","formengine:flexform:sorting-changed"].forEach((o=>{document.addEventListener(o,(()=>{t.destroy(),i.replace(n,e.configuration)}))}))}))}))}}