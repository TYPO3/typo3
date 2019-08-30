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
define(["require","exports","jquery","./LinkBrowser"],function(e,t,n,r){"use strict";return new class{constructor(){n(()=>{n("#ltelephoneform").on("submit",e=>{e.preventDefault();let t=n(e.currentTarget).find('[name="ltelephone"]').val();"tel:"!==t&&(0===t.indexOf("tel:")&&(t=t.substr(4)),r.finalizeFunction("tel:"+t))})})}}});