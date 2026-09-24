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

namespace TYPO3\CMS\IndexedSearch\Report\Status;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Tells which search words the MySQL fulltext index cannot find, because they
 * are shorter than the minimum word length the database server indexes.
 *
 * @internal
 */
final readonly class MysqlFulltextStatus implements StatusProviderInterface
{
    private const LANGUAGE_PATH = 'LLL:indexed_search.reports:';

    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @return Status[]
     */
    public function getStatus(): array
    {
        if (!(bool)$this->extensionConfiguration->get('indexed_search', 'useMysqlFulltext')) {
            return [];
        }
        $connection = $this->connectionPool->getConnectionForTable('index_fulltext');
        if (!$connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return [];
        }
        $engine = (string)$connection->executeQuery(
            'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['index_fulltext']
        )->fetchOne();
        $variable = strtolower($engine) === 'myisam' ? 'ft_min_word_len' : 'innodb_ft_min_token_size';
        $minimumWordLength = (int)$connection->executeQuery('SELECT @@' . $variable)->fetchOne();
        if ($minimumWordLength <= 0) {
            return [];
        }
        $languageService = $this->getLanguageService();
        return [
            'minimumWordLength' => new Status(
                $languageService->sL(self::LANGUAGE_PATH . 'status.minimumWordLength'),
                sprintf($languageService->sL(self::LANGUAGE_PATH . 'status.minimumWordLength.value'), $minimumWordLength),
                sprintf($languageService->sL(self::LANGUAGE_PATH . 'status.minimumWordLength.message'), $minimumWordLength, $variable),
                ContextualFeedbackSeverity::INFO
            ),
        ];
    }

    public function getLabel(): string
    {
        return self::LANGUAGE_PATH . 'statusProvider';
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
