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
var __createBinding=this&&this.__createBinding||(Object.create?function(e,t,r,i){void 0===i&&(i=r),Object.defineProperty(e,i,{enumerable:!0,get:function(){return t[r]}})}:function(e,t,r,i){void 0===i&&(i=r),e[i]=t[r]}),__setModuleDefault=this&&this.__setModuleDefault||(Object.create?function(e,t){Object.defineProperty(e,"default",{enumerable:!0,value:t})}:function(e,t){e.default=t}),__importStar=this&&this.__importStar||function(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var r in e)"default"!==r&&Object.prototype.hasOwnProperty.call(e,r)&&__createBinding(t,e,r);return __setModuleDefault(t,e),t};define(["require","exports"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.Helper=void 0;t.Helper=class{static dispatchFormEditor(t,r){Promise.all([t.app,t.mediator,t.viewModel].map(t=>new Promise((r,i)=>{e([t],r,i)}).then(__importStar))).then(e=>((e,t,i)=>{window.TYPO3.FORMEDITOR_APP=e.getInstance(r,t,i).run()})(...e))}static dispatchFormManager(t,r){Promise.all([t.app,t.viewModel].map(t=>new Promise((r,i)=>{e([t],r,i)}).then(__importStar))).then(e=>((e,t)=>{window.TYPO3.FORMMANAGER_APP=e.getInstance(r,t).run()})(...e))}}}));