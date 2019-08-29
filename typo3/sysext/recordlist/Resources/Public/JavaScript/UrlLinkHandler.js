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
define(["require","exports","jquery","./LinkBrowser"],function(n,r,e,i){"use strict";return new(function(){return function(){var n=this;this.link=function(n){n.preventDefault();var r=e(n.currentTarget).find('[name="lurl"]').val();""!==r&&i.finalizeFunction(r)},e(function(){e("#lurlform").on("submit",n.link)})}}())});