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

namespace TYPO3\CMS\Core\Resource\Search\QueryRestrictions;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\AbstractRestrictionContainer;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Restricts the result to available file mounts.
 * No restriction is added if the user is admin.
 */
class FolderMountsRestriction extends AbstractRestrictionContainer
{
    /**
     * @var BackendUserAuthentication
     */
    private $backendUser;

    /**
     * @var Folder[]|null
     */
    private $folderMounts;

    public function __construct(BackendUserAuthentication $backendUser)
    {
        $this->backendUser = $backendUser;
        $this->populateRestrictions();
    }

    private function populateRestrictions(): void
    {
        if ($this->backendUser->isAdmin()) {
            return;
        }
        foreach ($this->getFolderMounts() as $folder) {
            $this->add(new FolderRestriction($folder, true));
        }
    }

    /**
     * Same as parent method, but using OR composite, as files in either mounted folder should be found.
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        if (!$this->backendUser->isAdmin() && empty($this->getFolderMounts())) {
            // If the user isn't an admin but has no mounted folders, add an expression leading to an empty result
            return $expressionBuilder->andX('1=0');
        }
        $constraints = [];
        foreach ($this->restrictions as $restriction) {
            $constraints[] = $restriction->buildExpression($queriedTables, $expressionBuilder);
        }
        return $expressionBuilder->orX(...$constraints);
    }

    /**
     * @return Folder[]
     */
    private function getFolderMounts(): array
    {
        if ($this->folderMounts !== null) {
            return $this->folderMounts;
        }
        $this->folderMounts = [];
        $fileMounts = $this->backendUser->getFileMountRecords();
        foreach ($fileMounts as $fileMount) {
            $this->folderMounts[] = GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier($fileMount['base'] . ':' . $fileMount['path']);
        }

        return $this->folderMounts;
    }
}
