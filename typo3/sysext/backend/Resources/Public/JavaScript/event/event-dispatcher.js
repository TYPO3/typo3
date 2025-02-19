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
class c{static dispatchCustomEvent(e,n=null,s=!1){const t=new CustomEvent(e,{detail:n});s?typeof top<"u"&&top.document.dispatchEvent(t):document.dispatchEvent(t)}}export{c as EventDispatcher};
