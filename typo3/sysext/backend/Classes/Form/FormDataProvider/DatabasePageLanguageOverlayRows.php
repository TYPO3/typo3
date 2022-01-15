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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fill the "pageLanguageOverlayRows" part of the result array
 */
class DatabasePageLanguageOverlayRows implements FormDataProviderInterface
{
    /**
     * Fetch available page overlay records of page
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if ($result['effectivePid'] === 0) {
            // No overlays for records on pid 0 and not for new pages below root
            return $result;
        }

        $result['pageLanguageOverlayRows'] = $this->getDatabaseRows((int)$result['effectivePid']);

        return $result;
    }

    /**
     * Retrieve the requested overlay row from the database
     *
     * @param int $pid
     * @return array
     */
    protected function getDatabaseRows(int $pid): array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $workspaceId = $context->getPropertyFromAspect('workspace', 'id');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$workspaceId));

        $rows = $queryBuilder->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }
}
