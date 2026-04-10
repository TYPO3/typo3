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
function t(r){let n=null;for(let o=r;;o=o.parent){try{o.TYPO3&&(n=o)}catch{break}if(o===o.parent)break}return n}const e=t(window)??window,i=e.document;function p(){return opener?t(opener):null}export{p as openerTopWindow,i as topDocument,e as topWindow};
