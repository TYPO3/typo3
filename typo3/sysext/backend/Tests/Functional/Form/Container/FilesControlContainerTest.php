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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Container;

use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Form\Container\FilesControlContainer;
use TYPO3\CMS\Backend\Form\Event\CustomFileControlsEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FilesControlContainerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @test
     */
    public function customFileControlsEventIsCalled(): void
    {
        $customFileControlsEvent = null;
        $controls = ['foo', 'bar'];
        $databaseRow = [
            'uid' => 123,
        ];
        $fieldConfig = [
            'minitems' => 1,
            'maxitems' => 2,
        ];
        $fieldName = 'assets';
        $tableName = 'tx_table';
        $formFieldIdentifier = 'data-123-tx_table-123-assets';
        $formFieldName = 'data[tx_table][123][assets]';

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'custom-file-controls-listener',
            static function (CustomFileControlsEvent $event) use (&$customFileControlsEvent, $controls) {
                $customFileControlsEvent = $event;
                $event->setControls($controls);
                $event->setResultArray(['javaScriptModules' => ['fooJavaScriptModule']]);
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(CustomFileControlsEvent::class, 'custom-file-controls-listener');

        $subject = $this->get(FilesControlContainer::class);
        $subject->setData([
            'inlineData' => [],
            'inlineStructure' => [],
            'inlineFirstPid' => 123,
            'fieldName' => $fieldName,
            'tableName' => $tableName,
            'renderType' => 'file',
            'databaseRow' => $databaseRow,
            'tabAndInlineStack' => '',
            'request' => (new ServerRequest())
                ->withAttribute('route', new Route('', []))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE),
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'label' => 'foobar',
                    'config' => $fieldConfig,
                    'children' => [],
                ],
            ],
            'returnUrl' => '',
        ]);
        $result = $subject->render();

        self::assertInstanceOf(CustomFileControlsEvent::class, $customFileControlsEvent);
        self::assertContains('fooJavaScriptModule', $result['javaScriptModules']);
        self::assertContains('fooJavaScriptModule', $customFileControlsEvent->getResultArray()['javaScriptModules']);
        self::assertEquals($controls, $customFileControlsEvent->getControls());
        self::assertEquals($databaseRow, $customFileControlsEvent->getDatabaseRow());
        self::assertArrayHasKey('minitems', $customFileControlsEvent->getFieldConfig());
        self::assertArrayHasKey('maxitems', $customFileControlsEvent->getFieldConfig());
        self::assertEquals($fieldName, $customFileControlsEvent->getFieldName());
        self::assertEquals($formFieldIdentifier, $customFileControlsEvent->getFormFieldIdentifier());
        self::assertEquals($formFieldName, $customFileControlsEvent->getFormFieldName());
        self::assertEquals($tableName, $customFileControlsEvent->getTableName());
    }
}
