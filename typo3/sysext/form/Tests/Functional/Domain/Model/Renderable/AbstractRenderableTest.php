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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Model\Renderable;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AbstractRenderableTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected AbstractRenderable $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject = $this->buildAbstractRenderable();
    }

    #[Test]
    public function setOptionsResetsValidatorsIfDefined(): void
    {
        $this->subject->setOptions(['validators' => [
            ['identifier' => 'NotEmpty'],
            ['identifier' => 'EmailAddress'],
        ]]);

        self::assertCount(2, $this->subject->getValidators());

        $this->subject->setOptions(['validators' => []], true);

        self::assertCount(0, $this->subject->getValidators());
    }

    private function buildAbstractRenderable(): AbstractRenderable
    {
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationService = new ConfigurationService($configurationManager);
        $prototypeConfiguration = $configurationService->getPrototypeConfiguration('standard');

        $subject = new class () extends AbstractRenderable {};
        $subject->setIdentifier('Foo');
        $subject->setParentRenderable(new FormDefinition('foo', $prototypeConfiguration));

        return $subject;
    }
}
