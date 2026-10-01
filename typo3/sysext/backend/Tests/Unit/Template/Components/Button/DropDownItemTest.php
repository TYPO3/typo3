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

namespace TYPO3\CMS\Backend\Tests\Unit\Template\Components\Button;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DropDownItemTest extends UnitTestCase
{
    #[Test]
    public function renderAddsButtonTypeToButtonTagByDefault(): void
    {
        $item = new DropDownItem()->setTag('button')->setLabel('Info');
        self::assertStringContainsString('type="button"', $item->render());
    }

    #[Test]
    public function renderKeepsExplicitlyConfiguredType(): void
    {
        $item = new DropDownItem()->setTag('button')->setLabel('Info')->setAttributes(['type' => 'submit']);
        $result = $item->render();
        self::assertStringContainsString('type="submit"', $result);
        self::assertStringNotContainsString('type="button"', $result);
    }

    #[Test]
    public function renderDoesNotAddTypeToNonButtonTags(): void
    {
        $item = new DropDownItem()->setLabel('Info')->setHref('#');
        self::assertStringNotContainsString('type=', $item->render());
    }
}
