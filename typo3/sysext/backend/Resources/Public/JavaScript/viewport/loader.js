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
import{ScaffoldContentArea as o}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import{ContentNavigationSlotEnum as r}from"@typo3/backend/viewport/content-navigation.js";import t from"nprogress";class e{static start(){t.configure({parent:`${o.selector} > [slot="${r.content}"]`,showSpinner:!1}),t.start()}static finish(){t.done()}}export{e as default};
