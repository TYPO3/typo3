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
define(["require","exports","./AbstractAction","../Icons"],(function(e,t,c,n){"use strict";class s extends c.AbstractAction{async execute(e){return n.getIcon("spinner-circle-light",n.sizes.small).then(t=>{e.innerHTML=t}),await this.executeCallback()}async executeCallback(){return await this.callback()}}return s}));