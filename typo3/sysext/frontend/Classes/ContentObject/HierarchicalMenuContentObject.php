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

namespace TYPO3\CMS\Frontend\ContentObject;

use Psr\Container\ContainerInterface;

/**
 * Contains HMENU class object.
 */
class HierarchicalMenuContentObject extends AbstractContentObject
{
    /**
     * @param ContainerInterface $menuContentObjectLocator Locator of all menu content objects registered
     *                                                     via the "frontend.menucontentobject" tag, keyed by
     *                                                     their (upper cased) TypoScript identifier.
     */
    public function __construct(
        protected readonly ContainerInterface $menuContentObjectLocator,
    ) {}

    /**
     * Rendering the cObject, HMENU
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
            return '';
        }

        $theValue = '';
        $menuType = strtoupper((string)($conf[1] ?? ''));
        if ($this->menuContentObjectLocator->has($menuType)) {
            $register = $this->request->getAttribute('frontend.register.stack')->current();
            $menu = $this->menuContentObjectLocator->get($menuType);
            $countHMENU = (int)$register->get('count_HMENU', 0);
            $countHMENU++;
            $register->set('count_HMENU', $countHMENU);
            $register->set('count_HMENU_MENUOBJ', 0);
            $register->set('count_MENUOBJ', 0);
            $menu->parent_cObj = $this->getContentObjectRenderer();
            $menu->start(null, $this->getPageRepository(), '', $conf, 1, '', $this->request);
            $menu->makeMenu();
            $theValue .= $menu->writeMenu();
        }
        $wrap = $this->cObj->stdWrapValue('wrap', $conf);
        if ($wrap) {
            $theValue = $this->cObj->wrap($theValue, $wrap);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }
}
