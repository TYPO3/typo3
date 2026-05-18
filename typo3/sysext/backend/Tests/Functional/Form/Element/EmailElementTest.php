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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Element;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\EmailElement;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EmailElementTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_core.csv');
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->setBeUserByUid(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
    }

    #[Test]
    public function evalLowerIsHandedOverToTheClientSide(): void
    {
        $result = $this->getFormElementResult(['type' => 'email', 'eval' => 'lower']);

        self::assertStringContainsString('&quot;evalList&quot;:&quot;lower,trim&quot;', $result['html']);
    }

    #[Test]
    public function unsupportedEvalKeywordsAreNotHandedOverToTheClientSide(): void
    {
        $result = $this->getFormElementResult(['type' => 'email', 'eval' => 'upper,md5']);

        self::assertStringContainsString('&quot;evalList&quot;:&quot;trim&quot;', $result['html']);
    }

    private function getFormElementResult(array $config): array
    {
        $node = $this->get(EmailElement::class);
        $node->setData([
            'tableName' => 'tt_content',
            'fieldName' => 'email',
            'databaseRow' => [
                'uid' => 1,
            ],
            'processedTca' => [
                'columns' => [
                    'email' => [
                        'config' => $config,
                    ],
                ],
            ],
            'parameterArray' => [
                'itemFormElValue' => '',
                'itemFormElName' => 'email',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => $config,
                ],
            ],
        ]);
        return $node->render();
    }
}
