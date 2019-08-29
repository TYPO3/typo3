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
define(["require","exports","./Modifier/Resizable","./Modifier/Tabbable","jquery"],function(e,n,t,i,r){"use strict";return function(){return function(e){var n=this;this.element=null,r(function(){n.element=document.querySelector("#"+e),t.Resizable.enable(n.element),i.Tabbable.enable(n.element)})}}()});