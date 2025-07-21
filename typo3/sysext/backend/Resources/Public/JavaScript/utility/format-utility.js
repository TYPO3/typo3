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
class s{static fileSizeAsString(t,B="iec"){const a={iec:{base:1024,labels:[" "," KiB"," MiB"," GiB"," TiB"," PiB"," EiB"," ZiB"," YiB"]},si:{base:1e3,labels:[" "," kB"," MB"," GB"," TB"," PB"," EB"," ZB"," YB"]}}[B],i=t===0?0:Math.floor(Math.log(t)/Math.log(a.base));return+(t/Math.pow(a.base,i)).toFixed(2)+a.labels[i]}}export{s as FormatUtility};
