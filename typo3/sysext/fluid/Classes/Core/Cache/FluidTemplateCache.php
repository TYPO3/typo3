<?php
namespace TYPO3\CMS\Fluid\Core\Cache;

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

use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheWarmerInterface;
use TYPO3Fluid\Fluid\Core\Cache\StandardCacheWarmer;

/**
 * Class FluidTemplateCache
 *
 * Connector class that enables the TYPO3 cache called "fluid_template" to be operated with the
 * interface appropriate for the Fluid engine.
 *
 * @internal
 */
class FluidTemplateCache extends PhpFrontend implements FluidCacheInterface
{
    /**
     * @param null $name
     */
    public function flush($name = null)
    {
        parent::flush();
    }

    /**
     * @param string $entryIdentifier
     * @return mixed
     */
    public function get($entryIdentifier)
    {
        return $this->requireOnce($entryIdentifier);
    }

    /**
     * @param string $entryIdentifier
     * @param string $sourceCode
     * @param array $tags
     * @param int $lifetime
     * @throws InvalidDataException
     */
    public function set($entryIdentifier, $sourceCode, array $tags = [], $lifetime = null)
    {
        if (strpos($sourceCode, '<?php') === 0) {
            // Remove opening PHP tag; it is added by the cache backend to which
            // we delegate and would be duplicated if not removed.
            $sourceCode = substr($sourceCode, 6);
        }
        parent::set($entryIdentifier, $sourceCode, $tags, time() + 86400);
    }

    /**
     * @return FluidCacheWarmerInterface
     */
    public function getCacheWarmer()
    {
        return new StandardCacheWarmer();
    }
}
