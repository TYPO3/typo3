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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Mvc\Persistence\FormDefinitionOverrideProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormDefinitionOverrideProcessorTest extends UnitTestCase
{
    #[Test]
    public function processReplacesRecipientOptionsWithStructuredTypoScriptValues(): void
    {
        $subject = new FormDefinitionOverrideProcessor();
        $formDefinition = [
            'finishers' => [
                [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'recipients' => [
                            'old@example.org' => 'Old recipient',
                        ],
                    ],
                ],
            ],
        ];
        $overrides = [
            'finishers' => [
                0 => [
                    'options' => [
                        'recipients' => [
                            10 => [
                                'email' => 'test@test.de',
                                'name' => 'Test recipient',
                            ],
                            20 => [
                                'email' => 'second@example.org',
                                'name' => 'Second recipient',
                            ],
                        ],
                        'carbonCopyRecipients' => [
                            10 => [
                                'email' => 'cc@example.org',
                                'name' => 'CC recipient',
                            ],
                        ],
                        'blindCarbonCopyRecipients' => [
                            10 => [
                                'email' => 'bcc@example.org',
                                'name' => 'BCC recipient',
                            ],
                        ],
                        'replyToRecipients' => [
                            10 => [
                                'email' => 'reply@example.org',
                                'name' => 'Reply recipient',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame(
            [
                'finishers' => [
                    [
                        'identifier' => 'EmailToReceiver',
                        'options' => [
                            'recipients' => [
                                'test@test.de' => 'Test recipient',
                                'second@example.org' => 'Second recipient',
                            ],
                            'carbonCopyRecipients' => [
                                'cc@example.org' => 'CC recipient',
                            ],
                            'blindCarbonCopyRecipients' => [
                                'bcc@example.org' => 'BCC recipient',
                            ],
                            'replyToRecipients' => [
                                'reply@example.org' => 'Reply recipient',
                            ],
                        ],
                    ],
                ],
            ],
            $subject->process($formDefinition, $overrides),
        );
    }

    #[Test]
    public function processThrowsForRecipientAddressUsedAsTypoScriptKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1788434592);

        new FormDefinitionOverrideProcessor()->process(
            ['finishers' => []],
            [
                'finishers' => [
                    0 => [
                        'options' => [
                            'recipients' => [
                                'test@test.de' => 'Test recipient',
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
