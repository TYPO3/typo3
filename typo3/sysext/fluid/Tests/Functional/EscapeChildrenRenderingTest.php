<?php
namespace TYPO3\Fluid\Tests\Functional;

use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Fluid\View\TemplateView;

class EscapeChildrenRenderingTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test'];

    protected $coreExtensionsToLoad = ['fluid'];

    public function viewHelperTemplateSourcesDataProvider()
    {
        return [
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Tag syntax with children, properly encodes variable value' =>
            [
                '<ft:escapeChildrenEnabledAndEscapeOutputDisabled>{settings.test}</ft:escapeChildrenEnabledAndEscapeOutputDisabled>',
                '&lt;strong&gt;Bla&lt;/strong&gt;'
            ],
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Inline syntax with children, properly encodes variable value' =>
            [
                '{settings.test -> ft:escapeChildrenEnabledAndEscapeOutputDisabled()}',
                '&lt;strong&gt;Bla&lt;/strong&gt;'
            ],
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Tag syntax with argument, does not encode variable value' =>
            [
                '<ft:escapeChildrenEnabledAndEscapeOutputDisabled content="{settings.test}" />',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Inline syntax with argument, does not encode variable value' =>
            [
                '{ft:escapeChildrenEnabledAndEscapeOutputDisabled(content: settings.test)}',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Inline syntax with string, does not encode string value' =>
            [
                '{ft:escapeChildrenEnabledAndEscapeOutputDisabled(content: \'<strong>Bla</strong>\')}',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Inline syntax with argument in quotes, does encode variable value (encoded before passed to VH)' =>
            [
                '{ft:escapeChildrenEnabledAndEscapeOutputDisabled(content: \'{settings.test}\')}',
                '&lt;strong&gt;Bla&lt;/strong&gt;'
            ],
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Tag syntax with nested inline syntax and children rendering, does not encode variable value' =>
            [
                '<ft:escapeChildrenEnabledAndEscapeOutputDisabled content="{settings.test -> ft:escapeChildrenEnabledAndEscapeOutputDisabled()}" />',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenEnabledAndEscapeOutputDisabled: Tag syntax with nested inline syntax and argument in inline, does not encode variable value' =>
            [
                '<ft:escapeChildrenEnabledAndEscapeOutputDisabled content="{ft:escapeChildrenEnabledAndEscapeOutputDisabled(content: settings.test)}" />',
                '<strong>Bla</strong>'
            ],

            'EscapeChildrenDisabledAndEscapeOutputDisabled: Tag syntax with children, properly encodes variable value' =>
            [
                '<ft:escapeChildrenDisabledAndEscapeOutputDisabled>{settings.test}</ft:escapeChildrenDisabledAndEscapeOutputDisabled>',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenDisabledAndEscapeOutputDisabled: Inline syntax with children, properly encodes variable value' =>
            [
                '{settings.test -> ft:escapeChildrenDisabledAndEscapeOutputDisabled()}',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenDisabledAndEscapeOutputDisabled: Tag syntax with argument, does not encode variable value' =>
            [
                '<ft:escapeChildrenDisabledAndEscapeOutputDisabled content="{settings.test}" />',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenDisabledAndEscapeOutputDisabled: Inline syntax with argument, does not encode variable value' =>
            [
                '{ft:escapeChildrenDisabledAndEscapeOutputDisabled(content: settings.test)}',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenDisabledAndEscapeOutputDisabled: Inline syntax with string, does not encode string value' =>
            [
                '{ft:escapeChildrenDisabledAndEscapeOutputDisabled(content: \'<strong>Bla</strong>\')}',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenDisabledAndEscapeOutputDisabled: Inline syntax with argument in quotes, does encode variable value (encoded before passed to VH)' =>
            [
                '{ft:escapeChildrenDisabledAndEscapeOutputDisabled(content: \'{settings.test}\')}',
                '&lt;strong&gt;Bla&lt;/strong&gt;'
            ],
            'EscapeChildrenDisabledAndEscapeOutputDisabled: Tag syntax with nested inline syntax and children rendering, does not encode variable value' =>
            [
                '<ft:escapeChildrenDisabledAndEscapeOutputDisabled content="{settings.test -> ft:escapeChildrenDisabledAndEscapeOutputDisabled()}" />',
                '<strong>Bla</strong>'
            ],
            'EscapeChildrenDisabledAndEscapeOutputDisabled: Tag syntax with nested inline syntax and argument in inline, does not encode variable value' =>
            [
                '<ft:escapeChildrenDisabledAndEscapeOutputDisabled content="{ft:escapeChildrenDisabledAndEscapeOutputDisabled(content: settings.test)}" />',
                '<strong>Bla</strong>'
            ],

        ];
    }

    /**
     * @param string $viewHelperTemplate
     * @param string $expectedOutput
     *
     * @test
     * @dataProvider viewHelperTemplateSourcesDataProvider
     */
    public function renderingTest($viewHelperTemplate, $expectedOutput)
    {
        $view = new TemplateView();
        $view->assign('settings', ['test' => '<strong>Bla</strong>']);
        $templateString = '{namespace ft=TYPO3Fluid\\FluidTest\\ViewHelpers}';
        $templateString .= $viewHelperTemplate;
        $view->getRenderingContext()->getViewHelperResolver()->addNamespace('ft', 'TYPO3Fluid\\FluidTest\\ViewHelpers');
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($viewHelperTemplate);

        $this->assertSame($expectedOutput, $view->render());
    }
}
