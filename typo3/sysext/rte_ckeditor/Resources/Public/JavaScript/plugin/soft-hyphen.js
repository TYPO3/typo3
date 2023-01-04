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
import{Core}from"@typo3/ckeditor5-bundle.js";import Whitespace from"@typo3/rte-ckeditor/plugin/whitespace.js";export default class SoftHyphen extends Core.Plugin{init(){console.warn("The TYPO3 CKEditor5 SoftHyphen plugin is deprecated and will be removed with v13. Please use the Whitespace plugin instead.")}}SoftHyphen.pluginName="SoftHyphen",SoftHyphen.requires=[Whitespace];