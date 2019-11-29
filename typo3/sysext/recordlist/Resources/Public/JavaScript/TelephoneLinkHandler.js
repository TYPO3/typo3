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
define(["require","exports","jquery","./LinkBrowser"],(function(e,t,r,n){"use strict";return new class{constructor(){r(()=>{r("#ltelephoneform").on("submit",e=>{e.preventDefault();let t=r(e.currentTarget).find('[name="ltelephone"]').val();"tel:"!==t&&(t.startsWith("tel:")&&(t=t.substr(4)),n.finalizeFunction("tel:"+t))})})}}}));