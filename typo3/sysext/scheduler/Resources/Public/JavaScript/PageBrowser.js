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
define(["require","exports","jquery"],function(e,r,t){"use strict";return new class{constructor(){t(document).on("click",".t3js-pageBrowser",e=>{let r,s=t(e.currentTarget).data("url");(r=window.open(s,"Typo3WinBrowser","height=650,width=800,status=0,menubar=0,resizable=1,scrollbars=1")).focus()})}}});