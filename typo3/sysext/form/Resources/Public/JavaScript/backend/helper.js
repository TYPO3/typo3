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
import{loadModule as a}from"@typo3/core/java-script-item-processor.js";import r from"@typo3/core/document-service.js";class l{static dispatchFormEditor(o,t){r.ready().then(()=>{Promise.all([a(o.app),a(o.mediator),a(o.viewModel)]).then(e=>((d,i,n)=>{window.TYPO3.FORMEDITOR_APP=d.getInstance(t,i,n).run()})(...e))})}static dispatchFormManager(o,t){r.ready().then(()=>{Promise.all([a(o.app),a(o.viewModel)]).then(e=>((d,i)=>{window.TYPO3.FORMMANAGER_APP=d.getInstance(t,i).run()})(...e))})}}export{l as Helper};
