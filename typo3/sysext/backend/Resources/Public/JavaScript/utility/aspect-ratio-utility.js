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
class a{static{this.commonRatios=[[1,1],[5,4],[4,3],[3,2],[16,10],[16,9],[21,9],[2,1],[3,1],[4,5],[3,4],[2,3],[10,16],[9,16],[9,21],[1,2],[1,3]]}static find(n,t,e=.02){if(n<=0||t<=0)return null;const u=n/t,i=a.greatestCommonDivisor(Math.round(n),Math.round(t));let o=null;for(const r of a.commonRatios){const s=Math.abs(u-r[0]/r[1])/(r[0]/r[1]);(o===null||s<o.deviation)&&(o={ratio:r,deviation:s})}return o===null||o.deviation>e?null:{label:`${o.ratio[0]}:${o.ratio[1]}`,exact:Math.round(n)/i===o.ratio[0]&&Math.round(t)/i===o.ratio[1]}}static greatestCommonDivisor(n,t){return t===0?n:a.greatestCommonDivisor(t,n%t)}}export{a as AspectRatioUtility};
