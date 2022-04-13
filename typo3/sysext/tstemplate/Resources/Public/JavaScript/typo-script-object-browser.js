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
import"@typo3/backend/input/clearable.js";class TypoScriptObjectBrowser{constructor(){this.searchField=document.querySelector('input[name="searchValue"]'),this.searchResultShown=""!==this.searchField.value,this.searchField.clearable({onClear:e=>{this.searchResultShown&&e.closest("form").submit()}})}}export default new TypoScriptObjectBrowser;