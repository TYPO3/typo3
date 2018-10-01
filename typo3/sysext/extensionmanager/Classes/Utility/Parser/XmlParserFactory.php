<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

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
 * Factory for XML parsers.
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class XmlParserFactory
{
    /**
     * An array with instances of xml parsers.
     * This member is set in the getParserInstance() function.
     *
     * @var array
     */
    protected static $instance = [];

    /**
     * Keeps array of all available parsers.
     *
     * @todo This would better be moved to a global configuration array like
     * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']. (might require EM to be moved in a sysext)
     *
     * @var array
     */
    protected static $parsers = [
        'extension' => [
            \TYPO3\CMS\Extensionmanager\Utility\Parser\ExtensionXmlPushParser::class => 'ExtensionXmlPushParser.php',
            \TYPO3\CMS\Extensionmanager\Utility\Parser\ExtensionXmlPullParser::class => 'ExtensionXmlPullParser.php',
        ],
        'mirror' => [
            \TYPO3\CMS\Extensionmanager\Utility\Parser\MirrorXmlPushParser::class => 'MirrorXmlPushParser.php',
            \TYPO3\CMS\Extensionmanager\Utility\Parser\MirrorXmlPullParser::class=> 'MirrorXmlPullParser.php',
        ]
    ];

    /**
     * Obtains a xml parser instance.
     *
     * This function will return an instance of a class that implements
     * \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractExtensionXmlParser
     *
     * @param string $parserType type of parser, one of extension and mirror
     * @param string $excludeClassNames (optional) comma-separated list of class names
     * @return \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractExtensionXmlParser an instance of an extension.xml parser
     */
    public static function getParserInstance($parserType, $excludeClassNames = '')
    {
        if (!isset(self::$instance[$parserType]) || !is_object(self::$instance[$parserType]) || !empty($excludeClassNames)) {
            // reset instance
            self::$instance[$parserType] = ($objParser = null);
            foreach (self::$parsers[$parserType] as $className => $file) {
                if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($excludeClassNames, $className)) {
                    $objParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
                    if ($objParser->isAvailable()) {
                        self::$instance[$parserType] = &$objParser;
                        break;
                    }
                    $objParser = null;
                }
            }
        }
        return self::$instance[$parserType];
    }
}
