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

namespace TYPO3\CMS\Fluid\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ResolveRecordPropertyClosureTest extends FunctionalTestCase
{
    #[Test]
    public function recordPropertyClosureOfFlexIsResolved(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('{flex.some.link.url} // {flex.some.link.target} // {flex.some.link.class} // {flex.some.link.title}');
        $view = new TemplateView($context);
        $view->assign('flex', new FlexFormFieldValues(['some' => ['link' => new RecordPropertyClosure(fn(): TypolinkParameter => TypolinkParameter::createFromTypolinkParts(GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode('t3://page?uid=14 _blank class title')))]]));
        self::assertSame('t3://page?uid=14 // _blank // class // title', $view->render());
    }
}
