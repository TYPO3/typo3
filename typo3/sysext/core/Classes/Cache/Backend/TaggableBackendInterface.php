<?php
namespace TYPO3\CMS\Core\Cache\Backend;

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
 * A contract for a cache backend which supports tagging.
 */
interface TaggableBackendInterface extends BackendInterface
{
    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag);

    /**
     * Removes all cache entries of this cache which are tagged by any of the specified tags.
     *
     * @param string[] $tags List of tags
     */
    public function flushByTags(array $tags);

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     */
    public function findIdentifiersByTag($tag);
}
