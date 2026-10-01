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
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GenericButtonTest extends UnitTestCase
{
    #[Test]
    public function renderAddsButtonTypeToButtonTagByDefault(): void
    {
        $button = new GenericButton();
        self::assertStringContainsString('type="button"', $button->render());
    }

    #[Test]
    public function renderKeepsExplicitlyConfiguredType(): void
    {
        $button = new GenericButton()->setAttributes(['type' => 'submit']);
        $result = $button->render();
        self::assertStringContainsString('type="submit"', $result);
        self::assertStringNotContainsString('type="button"', $result);
    }

    #[Test]
    public function renderDoesNotAddTypeToNonButtonTags(): void
    {
        $button = new GenericButton()->setTag('typo3-backend-dispatch-modal-button');
        self::assertStringNotContainsString('type=', $button->render());
    }
}
