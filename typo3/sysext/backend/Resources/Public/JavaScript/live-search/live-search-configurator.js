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
class o{constructor(){this.renderers={},this.invokeHandlers={}}getRenderers(){return this.renderers}addRenderer(r,t,n){this.renderers[r]={module:t,callback:n}}getInvokeHandlers(){return this.invokeHandlers}addInvokeHandler(r,t,n){this.invokeHandlers[r+"_"+t]=n}}let e;top.TYPO3.LiveSearchConfigurator?e=top.TYPO3.LiveSearchConfigurator:(e=new o,top.TYPO3.LiveSearchConfigurator=e);var a=e;export{a as default};
