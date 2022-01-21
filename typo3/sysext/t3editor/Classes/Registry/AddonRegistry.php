<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\T3editor\Registry;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\T3editor\Addon;

/**
 * Registers and holds t3editor modes
 * @internal
 */
class AddonRegistry implements SingletonInterface
{
    /**
     * @var Addon[]
     */
    protected $registeredAddons = [];

    /**
     * Registers addons for global use in t3editor
     *
     * @param Addon $addon
     * @return self
     */
    public function register(Addon $addon): AddonRegistry
    {
        $this->registeredAddons[] = $addon;

        return $this;
    }

    public function getAddons(): array
    {
        return $this->registeredAddons;
    }

    /**
     * @param Addon[] $addons
     * @return array
     */
    public function compileSettings(array $addons): array
    {
        $settings = [];
        foreach ($addons as $addon) {
            $settings = array_merge($settings, $addon->getOptions());
        }

        return $settings;
    }
}
