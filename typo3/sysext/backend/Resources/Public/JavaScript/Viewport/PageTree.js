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
define(["require","exports"],(function(t,e){"use strict";return class{constructor(t){this.instance=null,this.instance=t}refreshTree(){null!==this.instance&&this.instance.refreshTree()}setTemporaryMountPoint(t){null!==this.instance&&this.instance.setTemporaryMountPoint(t)}unsetTemporaryMountPoint(){null!==this.instance&&this.instance.unsetTemporaryMountPoint()}selectNode(t){null!==this.instance&&this.instance.selectNode(t)}getFirstNode(){return null!==this.instance?this.instance.getFirstNode():{}}}}));