<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory for menu content objects. Allows overriding the default
 * types like 'GMENU' with an own implementation (only one possible)
 * and new types can be registered.
 */
class MenuContentObjectFactory implements SingletonInterface
{
    /**
     * Register of TypoScript keys to according render class
     *
     * @var array
     */
    protected $menuTypeToClassMapping = [
        'GMENU' => GraphicalMenuContentObject::class,
        'TMENU' => TextMenuContentObject::class,
        'IMGMENU' => ImageMenuContentObject::class,
        'JSMENU' => JavaScriptMenuContentObject::class,
    ];

    /**
     * Gets a typo script string like 'TMENU' and returns an object of this type
     *
     * @param string $type
     * @return AbstractMenuContentObject Menu object
     * @throws Exception\NoSuchMenuTypeException
     */
    public function getMenuObjectByType($type = '')
    {
        $upperCasedClassName = strtoupper($type);
        if (array_key_exists($upperCasedClassName, $this->menuTypeToClassMapping)) {
            $object = GeneralUtility::makeInstance($this->menuTypeToClassMapping[$upperCasedClassName]);
        } else {
            throw new Exception\NoSuchMenuTypeException(
                'Menu type ' . (string)$type . ' has no implementing class.',
                1363278130
            );
        }
        return $object;
    }

    /**
     * Register new menu type or override existing type
     *
     * @param string $type Menu type to be used in TypoScript
     * @param string $className Class rendering the menu
     * @throws \InvalidArgumentException
     */
    public function registerMenuType($type, $className)
    {
        if (!is_string($type) || !is_string($className)) {
            throw new \InvalidArgumentException(
                'type and className must be strings',
                1363429303
            );
        }
        $this->menuTypeToClassMapping[strtoupper($type)] = $className;
    }
}
