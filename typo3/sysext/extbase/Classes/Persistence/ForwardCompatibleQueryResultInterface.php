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

namespace TYPO3\CMS\Extbase\Persistence;

/**
 * Helper interface for v11 DI to see if a QueryResult can be autowired,
 * or if a fallback to ObjectManager needs to be done.
 *
 * @deprecated since v11, will be merged into QueryResultInterface in v12.
 */
interface ForwardCompatibleQueryResultInterface extends QueryResultInterface
{
    public function setQuery(QueryInterface $query): void;
}
