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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Factory;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ArrayFormFactoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function formDefinitionAfterBuildHasRequestSet(): void
    {
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseConfigurationManager = $this->get(ExtbaseConfigurationManagerInterface::class);
        $extbaseConfigurationManager->setRequest($request);

        $arrayFormFactory = $this->get(ArrayFormFactory::class);
        $configuration = [
            'label' => 'Form',
            'identifier' => 'form-1',
        ];

        $formDefinition = $arrayFormFactory->build($configuration, 'standard', $request);
        self::assertInstanceOf(ServerRequestInterface::class, $formDefinition->getRequest());
    }
}
