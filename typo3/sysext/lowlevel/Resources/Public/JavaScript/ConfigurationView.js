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
define(["require","exports","jquery","TYPO3/CMS/Backend/jquery.clearable"],function(e,s,r){"use strict";return new class{constructor(){this.$searchFields=r('input[name="searchString"]'),this.searchResultShown=""!==this.$searchFields.first().val(),this.$searchFields.clearable({onClear:()=>{this.searchResultShown&&r(this.$searchFields).closest("form").submit()}}),self.location.hash&&r("html, body").scrollTop((document.documentElement.scrollTop||document.body.scrollTop)-80)}}});