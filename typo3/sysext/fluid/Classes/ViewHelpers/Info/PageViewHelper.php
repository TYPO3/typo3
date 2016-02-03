<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Info;

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

/**
 * Page Information ViewHelper
 *
 * Fetches an array of page metadata contained in
 * ``$GLOBALS['TSFE']->page`` and either returns
 * the array or assigns it to template variables in
 * tag content if using the ``as`` argument.
 *
 * = Examples =
 *
 * <code title="With as argument">
 * <f:info.page as="pageInfo">
 *     The page title is {pageInfo.title}.
 * </f:info.page>
 * </code>
 *
 * <code title="Returning an array">
 * <f:alias map="{pageInfo: '{f:info.page()}'}">
 *     The page title is {pageInfo.title}.
 * </f:alias>
 * </code>
 */
class PageViewHelper extends AbstractInfoViewHelper
{
    /**
     * @return array
     */
    protected static function getData()
    {
        return static::getTypoScriptFrontendController()->page;
    }
}
