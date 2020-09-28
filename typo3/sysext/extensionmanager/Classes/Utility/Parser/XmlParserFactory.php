<?php

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

namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory for XML parsers.
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class XmlParserFactory
{
    /**
     * An array with instances of xml parsers.
     * This member is set in the getParserInstance() function.
     *
     * @var AbstractExtensionXmlParser
     */
    protected static $instance;

    /**
     * Keeps array of all available parsers.
     *
     * @var array
     */
    protected static $parsers = [
        ExtensionXmlPushParser::class,
        ExtensionXmlPullParser::class,
    ];

    /**
     * Obtains a xml parser instance.
     *
     * This function will return an instance of a class that implements
     * \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractExtensionXmlParser
     *
     * @return AbstractExtensionXmlParser an instance of an extension.xml parser
     */
    public static function getParserInstance()
    {
        if (!isset(self::$instance) || !is_object(self::$instance)) {
            // reset instance
            self::$instance = null;
            foreach (self::$parsers as $className) {
                /** @var AbstractExtensionXmlParser $objParser */
                $objParser = GeneralUtility::makeInstance($className);
                if ($objParser->isAvailable()) {
                    self::$instance = $objParser;
                    break;
                }
            }
        }
        return self::$instance;
    }
}
