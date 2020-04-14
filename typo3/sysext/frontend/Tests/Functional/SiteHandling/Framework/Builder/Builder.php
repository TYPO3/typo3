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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

class Builder
{
    public static function create(): self
    {
        return new static();
    }

    /**
     * @return EnhancerDeclaration[]
     */
    public function declareEnhancers(): array
    {
        $routePath = VariableValue::create(
            '/[[routePrefix]]/[[routeParameter]]',
            Variables::create(['routeParameter' => '{value}'])
        );
        $resolveValue = Variable::create('resolveValue', Variable::CAST_STRING);

        return [
            'Simple' => EnhancerDeclaration::create('Simple')
                ->withConfiguration([
                    'type' => 'Simple',
                    'routePath' => $routePath,
                    '_arguments' => [],
                ])
                ->withGenerateParameters([
                    VariableValue::create('&value=[[value]]')
                        ->withRequiredDefinedVariableNames('value'),
                ])
                ->withResolveArguments([
                    VariableItem::create('inArguments', [
                        'value' => $resolveValue
                    ]),
                ]),
            'Plugin' => EnhancerDeclaration::create('Plugin')
                ->withConfiguration([
                    'type' => 'Plugin',
                    'routePath' => $routePath,
                    'namespace' => 'testing',
                    '_arguments' => [],
                ])
                ->withGenerateParameters([
                    VariableValue::create('&testing[value]=[[value]]')
                        ->withRequiredDefinedVariableNames('value'),
                ])
                ->withResolveArguments([
                    VariableItem::create('inArguments', [
                        'testing' => [
                            'value' => $resolveValue,
                        ],
                    ]),
                ]),
            'Extbase' => EnhancerDeclaration::create('Extbase')
                ->withConfiguration([
                    'type' => 'Extbase',
                    'routes' => [
                        [
                            'routePath' => $routePath,
                            '_controller' => 'Link::index',
                            '_arguments' => [],
                        ],
                    ],
                    'extension' => 'testing',
                    'plugin' => 'link',
                ])
                ->withGenerateParameters([
                    VariableValue::create('&tx_testing_link[value]=[[value]]')
                        ->withRequiredDefinedVariableNames('value'),
                    '&tx_testing_link[controller]=Link&tx_testing_link[action]=index',
                ])
                ->withResolveArguments([
                    VariableItem::create('inArguments', [
                        'tx_testing_link' => [
                            'value' => $resolveValue,
                        ],
                    ]),
                    'staticArguments' => [
                        'tx_testing_link' => [
                            'controller' => 'Link',
                            'action' => 'index',
                        ],
                    ],
                ])
        ];
    }

    /**
     * @return PageTypeDeclaration[]
     */
    public function declarePageTypes(): array
    {
        $multipleTypesConfiguration = [
            'type' => 'PageType',
            'default' => '.html',
            'index' => 'index',
            'map' => [
                '.html' =>  0,
                'menu.json' =>  10,
                '.xml' => 20
            ],
        ];
        $singleTypeConfiguration = [
            'type' => 'PageType',
            'default' => '/',
            'index' => '/',
            'map' => [
                'menu.json' => 10,
            ],
        ];

        return [
            PageTypeDeclaration::create('null ".html"')
                ->withConfiguration($multipleTypesConfiguration)
                ->withResolveArguments(['pageType' => 0])
                ->withVariables(Variables::create(['pathSuffix' => '.html', 'index' => 'index'])),
            PageTypeDeclaration::create('0 ".html"')
                ->withConfiguration($multipleTypesConfiguration)
                ->withGenerateParameters(['&type=0'])
                ->withResolveArguments(['pageType' => 0])
                ->withVariables(Variables::create(['pathSuffix' => '.html', 'index' => 'index'])),
            PageTypeDeclaration::create('10 "/menu.json"')
                ->withConfiguration($multipleTypesConfiguration)
                ->withGenerateParameters(['&type=10'])
                ->withResolveArguments(['pageType' => 10])
                ->withVariables(Variables::create(['pathSuffix' => '/menu.json', 'index' => ''])),
            PageTypeDeclaration::create('20 ".xml"')
                ->withConfiguration($multipleTypesConfiguration)
                ->withGenerateParameters(['&type=20'])
                ->withResolveArguments(['pageType' => 20])
                ->withVariables(Variables::create(['pathSuffix' => '.xml', 'index' => 'index'])),
            PageTypeDeclaration::create('null "/"')
                ->withConfiguration($singleTypeConfiguration)
                ->withResolveArguments(['pageType' => 0])
                ->withVariables(Variables::create(['pathSuffix' => '/', 'index' => ''])),
            PageTypeDeclaration::create('0 "/"')
                ->withConfiguration($singleTypeConfiguration)
                ->withGenerateParameters(['&type=0'])
                ->withResolveArguments(['pageType' => 0])
                ->withVariables(Variables::create(['pathSuffix' => '/', 'index' => ''])),
        ];
    }

    public function compileEnhancerConfiguration(TestSet $testSet): array
    {
        $enhancerConfiguration = [];
        /** @var EnhancerDeclaration $enhancerDeclaration */
        foreach ($testSet->getApplicables(EnhancerDeclaration::class) as $enhancerDeclaration) {
            $enhancerConfiguration = array_replace_recursive(
                $enhancerConfiguration,
                (new VariableCompiler($enhancerDeclaration->getConfiguration(), $testSet->getVariables()))
                    ->compile()
                    ->getResults()
            );
        }
        /** @var AspectDeclaration $aspectDeclaration */
        foreach ($testSet->getApplicables(AspectDeclaration::class) as $aspectDeclaration) {
            $enhancerConfiguration['aspects'] = array_replace_recursive(
                $enhancerConfiguration['aspects'] ?? [],
                (new VariableCompiler($aspectDeclaration->getConfiguration(), $testSet->getVariables()))
                    ->compile()
                    ->getResults()
            );
        }
        return $enhancerConfiguration;
    }

    public function compilePageTypeConfiguration(TestSet $testSet): array
    {
        $pageTypeConfiguration = [];
        foreach ($testSet->getApplicables(PageTypeDeclaration::class) as $pageTypeDeclaration) {
            $pageTypeConfiguration = array_replace_recursive(
                $pageTypeConfiguration,
                (new VariableCompiler($pageTypeDeclaration->getConfiguration(), $testSet->getVariables()))
                    ->compile()
                    ->getResults()
            );
        }
        return $pageTypeConfiguration;
    }

    public function compileGenerateParameters(TestSet $testSet): string
    {
        $generateParameters = [];
        /** @var HasGenerateParameters $applicable */
        foreach ($testSet->getApplicables(HasGenerateParameters::class) as $applicable) {
            $generateParameters[] = $applicable->getGenerateParameters();
        }
        $generateParameters = (new VariableCompiler($generateParameters, $testSet->getVariables()))
            ->compile()
            ->getResults();
        return implode('', array_merge([], ...$generateParameters));
    }

    public function compileUrl(TestSet $testSet): string
    {
        return $testSet->getUrl()->apply($testSet->getVariables());
    }

    public function compileResolveArguments(TestSet $testSet): array
    {
        $resolveArguments = [];
        /** @var HasResolveArguments $applicable */
        foreach ($testSet->getApplicables(HasResolveArguments::class) as $applicable) {
            $resolveArguments = array_replace_recursive(
                $resolveArguments,
                (new VariableCompiler($applicable->getResolveArguments(), $testSet->getVariables()))
                    ->compile()
                    ->getResults()
            );
        }
        $resolveArguments['staticArguments'] = $resolveArguments['staticArguments'] ?? [];
        $resolveArguments['dynamicArguments'] = $resolveArguments['dynamicArguments'] ?? [];
        $resolveArguments['queryArguments'] = $resolveArguments['queryArguments'] ?? [];
        if (preg_match('#\?cHash=([a-z0-9]+)#i', $this->compileUrl($testSet), $matches)) {
            $resolveArguments['dynamicArguments']['cHash'] = $matches[1];
            $resolveArguments['queryArguments']['cHash'] = $matches[1];
        }

        return $resolveArguments;
    }
}
