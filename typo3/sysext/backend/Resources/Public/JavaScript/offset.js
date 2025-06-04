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
class t{constructor(h,i,e,s){this.left=h,this.top=i,this.width=e,this.height=s}get right(){return this.left+this.width}get bottom(){return this.top+this.height}static fromObject({left:h,top:i,width:e,height:s}){return new t(h,i,e,s)}clone(){return new t(this.left,this.top,this.width,this.height)}}export{t as Offset};
