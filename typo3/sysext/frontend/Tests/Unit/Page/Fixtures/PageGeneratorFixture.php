<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Page\Fixtures;

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

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageGenerator;

/**
 * Fixture for PageGenerator
 */
class PageGeneratorFixture extends PageGenerator
{
    /**
     * Public accessor for generateMetaTagHtml
     *
     * @param array $metaTagTypoScript TypoScript configuration for meta tags (e.g. $GLOBALS['TSFE']->pSetup['meta.'])
     * @param bool $xhtml Whether xhtml tag-style should be used. (e.g. pass $GLOBALS['TSFE']->xhtmlVersion here)
     * @param ContentObjectRenderer $cObj
     * @return array Array of HTML meta tags
     */
    public function callGenerateMetaTagHtml(array $metaTagTypoScript, $xhtml, ContentObjectRenderer $cObj)
    {
        return self::generateMetaTagHtml($metaTagTypoScript, $xhtml, $cObj);
    }

    /**
     * Public accessor for the initializeSearchWordDataInTsfe() method.
     */
    public function callInitializeSearchWordDataInTsfe()
    {
        static::initializeSearchWordDataInTsfe();
    }
}
