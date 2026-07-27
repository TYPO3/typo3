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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\DTO\FormConfiguration\Prototype;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\PrototypeConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PrototypeConfigurationTest extends UnitTestCase
{
    #[Test]
    public function fromArrayBuildsTypedFormEditorConfiguration(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formEditor' => [
                'maximumUndoSteps' => 10,
            ],
        ]);

        self::assertSame(10, $configuration->formEditor->maximumUndoSteps);
    }

    #[Test]
    public function fromArrayProvidesEmptyFormEditorConfigurationWhenMissing(): void
    {
        $configuration = PrototypeConfiguration::fromArray([]);

        self::assertSame(0, $configuration->formEditor->maximumUndoSteps);
    }

    #[Test]
    public function rawConfigurationIsPreservedAsEscapeHatch(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formElementsDefinition' => [
                'Form' => ['renderingOptions' => ['submitButtonLabel' => 'Submit']],
            ],
        ]);

        self::assertSame(
            'Submit',
            $configuration->get('formElementsDefinition.Form.renderingOptions.submitButtonLabel'),
        );
        self::assertTrue($configuration->has('formElementsDefinition.Form'));
        self::assertFalse($configuration->has('finishersDefinition'));
    }
}
