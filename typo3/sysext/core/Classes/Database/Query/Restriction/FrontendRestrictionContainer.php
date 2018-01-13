<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * A collection of restrictions to be used in frontend context.
 * This is a replacement for PageRepository::enableFields()
 */
class FrontendRestrictionContainer extends AbstractRestrictionContainer
{
    /**
     * @var QueryRestrictionInterface[]
     */
    protected $defaultRestrictionTypes = [
        DeletedRestriction::class,
        FrontendWorkspaceRestriction::class,
        HiddenRestriction::class,
        StartTimeRestriction::class,
        EndTimeRestriction::class,
        FrontendGroupRestriction::class,
    ];

    /**
     * FrontendRestrictionContainer constructor.
     * Initializes the default restrictions for frontend requests
     */
    public function __construct()
    {
        foreach ($this->defaultRestrictionTypes as $restrictionType) {
            $this->add($this->createRestriction($restrictionType));
        }
    }

    /**
     * Main method to build expressions for given tables
     * Iterates over all registered restrictions and removes the hidden restriction if preview is requested
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        /** @var TypoScriptFrontendController $typoScriptFrontendController */
        $typoScriptFrontendController = $GLOBALS['TSFE'];
        foreach ($this->restrictions as $restriction) {
            foreach ($queriedTables as $tableAlias => $tableName) {
                $disableRestriction = false;
                if ($restriction instanceof HiddenRestriction) {
                    // If display of hidden records is requested, we must disable the hidden restriction.
                    $disableRestriction = $tableName === 'pages' ? $typoScriptFrontendController->showHiddenPage : $typoScriptFrontendController->showHiddenRecords;
                }
                if (!$disableRestriction) {
                    $constraints[] = $restriction->buildExpression([$tableAlias => $tableName], $expressionBuilder);
                }
            }
        }
        return $expressionBuilder->andX(...$constraints);
    }
}
