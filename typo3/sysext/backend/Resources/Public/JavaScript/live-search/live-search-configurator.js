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
class LiveSearchConfigurator{constructor(){this.renderers={},this.invokeHandlers={}}getRenderers(){return this.renderers}addRenderer(r,e,t){this.renderers[r]={module:e,callback:t}}getInvokeHandlers(){return this.invokeHandlers}addInvokeHandler(r,e,t){this.invokeHandlers[r+"_"+e]=t}}let configuratorObject;top.TYPO3.LiveSearchConfigurator?configuratorObject=top.TYPO3.LiveSearchConfigurator:(configuratorObject=new LiveSearchConfigurator,top.TYPO3.LiveSearchConfigurator=configuratorObject);export default configuratorObject;