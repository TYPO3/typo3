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
function l(e){return(e??"").split(",").map(t=>t.trim()).filter(t=>t!=="")}function o(e,t){if(t===null)return!0;if(l(t.dataset.disallowedContentTypes).includes(e))return!1;const n=l(t.dataset.allowedContentTypes);return!(n.length>0&&!n.includes(e))}function s(e,t){return o(e,t.closest("[data-colpos]"))}export{s as isContentTypeAllowedForTarget,o as isContentTypeAllowedInColumn};
