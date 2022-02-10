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
import{loadModule}from"@typo3/core/java-script-item-processor.js";export class Helper{static dispatchFormEditor(o,e){Promise.all([loadModule(o.app),loadModule(o.mediator),loadModule(o.viewModel)]).then(o=>((o,a,d)=>{window.TYPO3.FORMEDITOR_APP=o.getInstance(e,a,d).run()})(...o))}static dispatchFormManager(o,e){Promise.all([loadModule(o.app),loadModule(o.viewModel)]).then(o=>((o,a)=>{window.TYPO3.FORMMANAGER_APP=o.getInstance(e,a).run()})(...o))}}