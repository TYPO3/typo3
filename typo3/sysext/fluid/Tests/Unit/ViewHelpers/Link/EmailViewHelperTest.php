<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

use TYPO3\CMS\Fluid\ViewHelpers\Link\EmailViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test case
 */
class EmailViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var EmailViewHelper
     */
    protected $viewHelper;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $cObjBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->cObj = $this->createMock(ContentObjectRenderer::class);
        $this->viewHelper = $this->getAccessibleMock(EmailViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['setTagName', 'addAttribute', 'setContent'])
            ->getMock();
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'mailto:some@email.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->setTagBuilder($mockTagBuilder);
        $this->viewHelper->expects(self::any())->method('renderChildren')->willReturn('some content');
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'email' => 'some@email.tld',
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderSetsTagContentToEmailIfRenderChildrenReturnNull()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['setTagName', 'addAttribute', 'setContent'])
            ->getMock();
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some@email.tld');
        $this->viewHelper->setTagBuilder($mockTagBuilder);
        $this->viewHelper->expects(self::any())->method('renderChildren')->willReturn(null);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'email' => 'some@email.tld',
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @return array
     */
    public function renderEncodesEmailInFrontendDataProvider()
    {
        return [
            'Plain email' => [
                'some@email.tld',
                0,
                '<a href="mailto:some@email.tld">some@email.tld</a>',
            ],
            'Plain email with spam protection' => [
                'some@email.tld',
                1,
                '<a href="javascript:linkTo_UnCryptMailto(%27nbjmup%2BtpnfAfnbjm%5C%2Fume%27);">some(at)email.tld</a>',
            ],
            'Plain email with ascii spam protection' => [
                'some@email.tld',
                'ascii',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#115;&#111;&#109;&#101;&#64;&#101;&#109;&#97;&#105;&#108;&#46;&#116;&#108;&#100;">some(at)email.tld</a>',
            ],
            'Susceptible email' => [
                '"><script>alert(\'email\')</script>',
                0,
                '<a href="mailto:&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;">&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;</a>',
            ],
            'Susceptible email with spam protection' => [
                '"><script>alert(\'email\')</script>',
                1,
                '<a href="javascript:linkTo_UnCryptMailto(%27nbjmup%2B%5Cu0022%5Cu003E%5Cu003Ctdsjqu%5Cu003Ebmfsu%28%5Cu0027fnbjm%5Cu0027%29%5Cu003C0tdsjqu%5Cu003E%27);">&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;</a>',
            ],
            'Susceptible email with ascii spam protection' => [
                '"><script>alert(\'email\')</script>',
                'ascii',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#34;&#62;&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#101;&#109;&#97;&#105;&#108;&#39;&#41;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;">&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;</a>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderEncodesEmailInFrontendDataProvider
     * @param string $email
     * @param string $spamProtectEmailAddresses
     * @param string $expected
     */
    public function renderEncodesEmailInFrontend($email, $spamProtectEmailAddresses, $expected)
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->cObj = new ContentObjectRenderer();
        $tsfe->spamProtectEmailAddresses = $spamProtectEmailAddresses;
        $tsfe->config = [
            'config' => [
                'spamProtectEmailAddresses_atSubst' => '',
                'spamProtectEmailAddresses_lastDotSubst' => '',
            ],
        ];
        $GLOBALS['TSFE'] = $tsfe;
        $viewHelper = $this->getMockBuilder(EmailViewHelper::class)
            ->setMethods(['isFrontendAvailable', 'renderChildren'])
            ->getMock();
        $viewHelper->expects(self::once())->method('isFrontendAvailable')->willReturn(true);
        $viewHelper->expects(self::once())->method('renderChildren')->willReturn(null);
        $viewHelper->setArguments([
            'email' => $email,
        ]);
        $viewHelper->initialize();
        self::assertSame(
            $expected,
            $viewHelper->render()
        );
    }
}
