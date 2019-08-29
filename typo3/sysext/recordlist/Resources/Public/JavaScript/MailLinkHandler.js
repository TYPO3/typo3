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
define(["require","exports","jquery","./LinkBrowser"],function(n,t,i,r){"use strict";return new(function(){return function(){i(function(){i("#lmailform").on("submit",function(n){n.preventDefault();var t=i(n.currentTarget).find('[name="lemail"]').val();if("mailto:"!==t){for(;"mailto:"===t.substr(0,7);)t=t.substr(7);r.finalizeFunction("mailto:"+t)}})})}}())});