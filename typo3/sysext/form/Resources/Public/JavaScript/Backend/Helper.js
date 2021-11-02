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
define(["require","exports"],(function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.Helper=void 0;t.Helper=class{static dispatchFormEditor(t,i){e([t.app,t.mediator,t.viewModel],(e,t,r)=>{window.TYPO3.FORMEDITOR_APP=e.getInstance(i,t,r).run()})}static dispatchFormManager(t,i){e([t.app,t.viewModel],(e,t)=>{window.TYPO3.FORMMANAGER_APP=e.getInstance(i,t).run()})}}}));