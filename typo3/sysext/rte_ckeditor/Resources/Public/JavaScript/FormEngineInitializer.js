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
var __createBinding=this&&this.__createBinding||(Object.create?function(e,t,i,n){void 0===n&&(n=i),Object.defineProperty(e,n,{enumerable:!0,get:function(){return t[i]}})}:function(e,t,i,n){void 0===n&&(n=i),e[n]=t[i]}),__setModuleDefault=this&&this.__setModuleDefault||(Object.create?function(e,t){Object.defineProperty(e,"default",{enumerable:!0,value:t})}:function(e,t){e.default=t}),__importStar=this&&this.__importStar||function(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var i in e)"default"!==i&&Object.prototype.hasOwnProperty.call(e,i)&&__createBinding(t,e,i);return __setModuleDefault(t,e),t},__importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],(function(e,t,i,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.FormEngineInitializer=void 0,i=__importDefault(i);t.FormEngineInitializer=class{static initializeCKEditor(t){new Promise((t,i)=>{e(["ckeditor"],t,i)}).then(__importStar).then(({default:e})=>{e.timestamp+="-"+t.configurationHash,t.externalPlugins.forEach(t=>e.plugins.addExternal(t.name,t.resource,"")),i.default(()=>{const a=t.fieldId,r="#"+i.default.escapeSelector(a);e.replace(a,t.configuration);const o=e.instances[a];o.on("change",e=>{let t=e.sender.commands;o.updateElement(),n.Validation.validateField(i.default(r)),n.Validation.markFieldAsChanged(i.default(r)),void 0!==t.maximize&&1===t.maximize.state&&o.on("maximize",e=>{i.default(this).off("maximize"),n.Validation.markFieldAsChanged(i.default(r))})}),o.on("mode",e=>{if("source"===e.editor.mode){const e=o.editable();e.attachListener(e,"change",()=>{n.Validation.markFieldAsChanged(i.default(r))})}}),document.addEventListener("inline:sorting-changed",()=>{o.destroy(),e.replace(a,t.configuration)}),document.addEventListener("formengine:flexform:sorting-changed",()=>{o.destroy(),e.replace(a,t.configuration)})})})}}}));