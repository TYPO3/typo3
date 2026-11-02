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

namespace TYPO3\CMS\Core\Security\Authorization\Policy;

/**
 * The scopes TYPO3 Core decides on: what is to be done with a subject,
 * passed as attribute to isGranted(Scope::WRITE, $schema). A voter of an
 * extension may decide on attributes of its own, prefixed with its
 * extension key. An attribute no voter decides on is denied.
 */
final class Scope
{
    /**
     * @internal not part of TYPO3 Core API yet, still experimental as part of the
     *           authentication modernization groundwork
     */
    public const string READ = 'read';

    /**
     * Any change of a resource: creating, updating or deleting it. Grants
     * of tables, fields and languages do not tell these changes apart, so
     * TYPO3 Core asks for this scope only and a voter restricting changes
     * decides on it.
     *
     * @internal not part of TYPO3 Core API yet, still experimental as part of the
     *           authentication modernization groundwork
     */
    public const string WRITE = 'write';

    /**
     * Creating a resource, e.g. a subpage. Only for resources whose grants
     * tell creating, updating and deleting apart, as page permissions do.
     *
     * @internal not part of TYPO3 Core API yet, still experimental as part of the
     *           authentication modernization groundwork
     */
    public const string CREATE = 'create';

    /**
     * Updating a resource, e.g. the properties of a page, see CREATE
     *
     * @internal not part of TYPO3 Core API yet, still experimental as part of the
     *           authentication modernization groundwork
     */
    public const string UPDATE = 'update';

    /**
     * Deleting a resource, e.g. a page, see CREATE
     *
     * @internal not part of TYPO3 Core API yet, still experimental as part of the
     *           authentication modernization groundwork
     */
    public const string DELETE = 'delete';

    /**
     * Creating, changing and deleting the content of a page
     *
     * @internal not part of TYPO3 Core API yet, still experimental as part of the
     *           authentication modernization groundwork
     */
    public const string UPDATE_CONTENT = 'update_content';

    /**
     * Accessing a resource, e.g. a backend module
     */
    public const string ACCESS = 'access';
}
