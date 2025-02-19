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
import r from"@typo3/backend/live-search/live-search-configurator.js";function d(e){r.addInvokeHandler(e,"open_module",o=>{TYPO3.ModuleMenu.App.showModule(o.extraData.moduleIdentifier)})}export{d as registerType};
