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
define(["require","exports","jquery","./LinkBrowser"],function(e,n,t,r){"use strict";return new function(){t(function(){t("#ltelephoneform").on("submit",function(e){e.preventDefault();var n=t(e.currentTarget).find('[name="ltelephone"]').val();"tel:"!==n&&(0===n.indexOf("tel:")&&(n=n.substr(4)),r.finalizeFunction("tel:"+n))})})}});