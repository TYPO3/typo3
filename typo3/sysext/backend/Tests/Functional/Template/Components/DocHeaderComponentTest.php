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

namespace TYPO3\CMS\Backend\Tests\Functional\Template\Components;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Template\Components\MenuRegistry;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DocHeaderComponentTest extends FunctionalTestCase
{
    private ServerRequestInterface $request;

    private DocHeaderComponent $subject;

    private ComponentFactory $componentFactory;

    private MenuRegistry $registry;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(DocHeaderComponent::class);
        $this->registry = $this->subject->getMenuRegistry();
        $this->componentFactory = $this->get(ComponentFactory::class);

        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $request = new ServerRequest('http://www.example.com/');
        $this->request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

    }

    #[Test]
    public function singleMenuCompilesDocHeaderComponent(): void
    {
        $menu1 = $this->componentFactory->createMenu();
        $menu1->setIdentifier('SomeModule');
        $menu1->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle('Item 1.1')
                ->setHref('#')
        );
        $menu1->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle('Item 1.2')
                ->setHref('#')
        );

        $this->registry->addMenu($menu1);

        $docHeader = $this->subject->docHeaderContent($this->request);
        self::assertIsArray($docHeader);
        self::assertIsArray($docHeader['buttons']);
        self::assertCount(1, $docHeader['buttons']['left'][0]);
        $dropdownButton = $docHeader['buttons']['left'][0][0];
        self::assertInstanceOf(DropDownButton::class, $dropdownButton);
        self::assertCount(2, $dropdownButton->getItems());
        $firstItem = $dropdownButton->getItems()[0];
        self::assertInstanceOf(DropDownRadio::class, $firstItem);
        self::assertSame('Item 1.1', $firstItem->getTitle());
    }

    #[Test]
    public function singleMenuDoesNotCompileWhenDocHeaderComponentHasOnlyOneItem(): void
    {
        $menu1 = $this->componentFactory->createMenu();
        $menu1->setIdentifier('SomeModule');
        $menu1->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle('Item 1.1')
                ->setHref('#')
        );
        $this->registry->addMenu($menu1);

        $docHeader = $this->subject->docHeaderContent($this->request);
        self::assertIsArray($docHeader);
        self::assertIsArray($docHeader['buttons']);
        self::assertArrayNotHasKey('left', $docHeader);
    }

    #[Test]
    public function multipleMenusButOnlyOneHavingItemsCompilesDocHeaderComponent(): void
    {
        $menu1 = $this->componentFactory->createMenu();
        $menu1->setIdentifier('SomeModule');
        $menu1->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle('Item 1.1')
                ->setHref('#')
        );
        $menu1->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle('Item 1.2')
                ->setHref('#')
        );

        $menu2 = $this->componentFactory->createMenu();
        $menu2->setIdentifier('SomeOtherModule');
        // Note: No items means it will be filtered out as an empty menu.

        $this->registry
            ->addMenu($menu1)
            ->addMenu($menu2);

        $docHeader = $this->subject->docHeaderContent($this->request);
        self::assertIsArray($docHeader);
        self::assertIsArray($docHeader['buttons']);
        self::assertCount(1, $docHeader['buttons']['left'][0]);
        $dropdownButton = $docHeader['buttons']['left'][0][0];
        self::assertInstanceOf(DropDownButton::class, $dropdownButton);
        self::assertCount(2, $dropdownButton->getItems());
        $firstItem = $dropdownButton->getItems()[0];
        self::assertInstanceOf(DropDownRadio::class, $firstItem);
        self::assertSame('Item 1.1', $firstItem->getTitle());
    }

    #[Test]
    public function multipleMenusLetDocHeaderComponentThrowExceptionOnCompilation(): void
    {
        $menu1 = $this->componentFactory->createMenu();
        $menu1->setIdentifier('SomeModule');
        $menu1->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle('Item 1.1')
                ->setHref('#')
        );

        $menu2 = $this->componentFactory->createMenu();
        $menu2->setIdentifier('SomeOtherModule');
        $menu2->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle('Item 2.1')
                ->setHref('#')
        );

        $this->registry
            ->addMenu($menu1)
            ->addMenu($menu2);

        $this->expectExceptionCode(1783447740);
        $this->subject->docHeaderContent($this->request);
    }
}
