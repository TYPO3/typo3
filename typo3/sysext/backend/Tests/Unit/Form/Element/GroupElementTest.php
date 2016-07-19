<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

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

use Prophecy\Argument;
use TYPO3\CMS\Backend\Form\Element\GroupElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class GroupElementTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderThrowsExceptionIfDbRecordsDoNotContainTableName()
    {
        $data = [
            'parameterArray' => [
                'fieldConf' => [
                    'config' => [
                        'allowed' => 'aForeignTable',
                        'internal_type' => 'db',
                        'show_thumbs' => true,
                    ],
                ],
                // This should trigger the exception, a correct value would be 'aForeignTable_42|aLabel'
                'itemFormElValue' => 42
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);
        $backendUserAuthentication = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication->reveal();
        $backendUserAuthentication->getPagePermsClause(1)->willReturn(1);

        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $subject = new GroupElement($nodeFactoryProphecy->reveal(), $data);

        $this->setExpectedException(\RuntimeException::class, 1468149217);

        $subject->render();
    }
}
