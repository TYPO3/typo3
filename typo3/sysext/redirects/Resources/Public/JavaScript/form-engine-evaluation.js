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
import r from"@typo3/backend/form-engine-validation.js";class o{static registerCustomEvaluation(t){r.registerCustomEvaluation(t,o.evaluateSourceHost)}static evaluateSourceHost(t){return t==="*"?t:(t.includes("://")||(t="http://"+t),new URL(t).host)}}export{o as FormEngineEvaluation};
