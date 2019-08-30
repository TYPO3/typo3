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
define(["require","exports","jquery","./LinkBrowser"],function(e,r,t,i){"use strict";return new class{constructor(){t(()=>{t("#lmailform").on("submit",e=>{e.preventDefault();let r=t(e.currentTarget).find('[name="lemail"]').val();if("mailto:"!==r){for(;"mailto:"===r.substr(0,7);)r=r.substr(7);i.finalizeFunction("mailto:"+r)}})})}}});