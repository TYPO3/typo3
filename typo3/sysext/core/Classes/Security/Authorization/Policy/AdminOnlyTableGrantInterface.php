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

use TYPO3\CMS\Core\Authentication\Principal;

/**
 * Grants a table marked "adminOnly" to a backend user who is no
 * administrator, e.g. be_users to an editor who manages other editors.
 *
 * Grants of backend groups never cover such a table, and a voter of an
 * extension cannot grant it, as the TableVoter denies it. This is the only
 * way to grant it: the policy decision point asks every implementation,
 * and a single grant suffices. Voters of extensions still restrict the
 * decision. The DataHandler does not ask voters yet and keeps writing
 * admin only tables for administrators only.
 *
 * Granting a table grants every field of it that is no exclude field.
 * be_users marks no field as exclude field: granting be_users grants
 * changing "admin", "password" and "usergroup" as well. Restrict the fields
 * the grant is not meant for with a voter of the extension.
 *
 * Implementations are registered by autoconfiguration.
 *
 * @internal Experimental, not yet public API: it may still change until
 * the checks of BackendUserAuthentication are migrated to the voters.
 */
interface AdminOnlyTableGrantInterface
{
    public const string TAG_NAME = 'security.admin_only_table_grant';

    /**
     * Whether the backend principal, who is no administrator, may read or
     * change records of the admin only table
     *
     * @param string $scope Scope::READ or Scope::WRITE
     * @param string $table the main table, never a sub-schema
     */
    public function isGranted(Principal $principal, string $scope, string $table): bool;
}
