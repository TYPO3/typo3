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
define(["require","exports","TYPO3/CMS/Backend/Input/Clearable"],(function(e,s){"use strict";return new class{constructor(){this.searchField=document.querySelector('input[name="search_field"]'),this.searchResultShown=""!==this.searchField.value,this.searchField.clearable({onClear:e=>{this.searchResultShown&&e.closest("form").submit()}}),self.location.hash&&window.scrollTo(window.pageXOffset,window.pageYOffset-40)}}}));