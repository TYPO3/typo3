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
export class Offset{constructor(t,h,e,i){this.left=t,this.top=h,this.width=e,this.height=i}get right(){return this.left+this.width}get bottom(){return this.top+this.height}static fromObject({left:t,top:h,width:e,height:i}){return new Offset(t,h,e,i)}clone(){return new Offset(this.left,this.top,this.width,this.height)}}