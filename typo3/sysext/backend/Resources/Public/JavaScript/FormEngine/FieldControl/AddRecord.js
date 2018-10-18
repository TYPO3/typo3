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
define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],function(e,t,n,r){"use strict";return function(e){var t=this;this.controlElement=null,this.registerClickHandler=function(e){e.preventDefault(),r.preventFollowLinkIfNotSaved(t.controlElement.getAttribute("href"))},n(function(){t.controlElement=document.querySelector(e),t.controlElement.addEventListener("click",t.registerClickHandler)})}});