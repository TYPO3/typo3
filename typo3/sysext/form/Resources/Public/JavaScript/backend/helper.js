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
import{loadModule}from"@typo3/core/java-script-item-processor.js";import DocumentService from"@typo3/core/document-service.js";export class Helper{static dispatchFormEditor(e,o){DocumentService.ready().then((()=>{Promise.all([loadModule(e.app),loadModule(e.mediator),loadModule(e.viewModel)]).then((e=>((e,t,r)=>{window.TYPO3.FORMEDITOR_APP=e.getInstance(o,t,r).run()})(...e)))}))}static dispatchFormManager(e,o){DocumentService.ready().then((()=>{Promise.all([loadModule(e.app),loadModule(e.viewModel)]).then((e=>((e,t)=>{window.TYPO3.FORMMANAGER_APP=e.getInstance(o,t).run()})(...e)))}))}}