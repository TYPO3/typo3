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
export class Offset{constructor(t,h,i,s){this.left=t,this.top=h,this.width=i,this.height=s}get right(){return this.left+this.width}get bottom(){return this.top+this.height}clone(){return new Offset(this.left,this.top,this.width,this.height)}}