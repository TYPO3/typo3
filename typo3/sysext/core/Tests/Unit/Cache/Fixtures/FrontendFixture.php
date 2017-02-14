<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Fixtures;

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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
* Fixture implementing frontend
*/
class FrontendFixture implements FrontendInterface
{
    /**
     * @var string
     */
    protected $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getBackend()
    {
    }

    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
    }

    public function get($entryIdentifier)
    {
    }

    public function getByTag($tag)
    {
    }

    public function has($entryIdentifier)
    {
    }

    public function remove($entryIdentifier)
    {
    }

    public function flush()
    {
    }

    public function flushByTag($tag)
    {
    }

    public function flushByTags(array $tags)
    {
    }

    public function collectGarbage()
    {
    }

    public function isValidEntryIdentifier($identifier)
    {
    }

    public function isValidTag($tag)
    {
    }
}
