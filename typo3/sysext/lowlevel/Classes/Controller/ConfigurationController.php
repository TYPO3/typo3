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

namespace TYPO3\CMS\Lowlevel\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderInterface;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;

/**
 * View configuration arrays in the backend. This is the "Configuration" main module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
final class ConfigurationController
{
    public function __construct(
        private readonly ProviderRegistry $configurationProviderRegistry,
        private readonly UriBuilder $uriBuilder,
        private readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {}

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();

        $moduleData = $request->getAttribute('moduleData');
        $providers = $this->configurationProviderRegistry->getProviders();
        // Validate requested "tree"
        $moduleData->clean('tree', array_keys($providers));
        $selectedProviderIdentifier = $moduleData->get('tree');
        $selectedProvider = $this->configurationProviderRegistry->getProvider($selectedProviderIdentifier);
        $selectedProviderLabel = $selectedProvider->getLabel();
        $selectedProviderLabelHash = hash('xxh3', $selectedProviderLabel);
        $configurationArray = $selectedProvider->getConfiguration();

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang:module.configuration.title'), $selectedProviderLabel);
        $this->addProviderDropDownToDocHeader($view, $providers, $selectedProvider);
        $this->addShortcutButtonToDocHeader($view, $selectedProvider, $selectedProviderIdentifier);
        $view->assignMultiple([
            'tree' => $this->renderTree($configurationArray, $selectedProviderLabelHash),
            'treeName' => $selectedProviderLabel,
            'treeLabelHash' => $selectedProviderLabelHash,
        ]);

        return $view->renderResponse('Configuration');
    }

    /**
     * We're rendering the trees directly in PHP for two reasons:
     * * Performance of Fluid is not good enough when dealing with large trees like TCA
     * * It's a bit hard to deal with the object details in Fluid
     */
    private function renderTree(array|\ArrayObject $tree, string $labelHash, string $incomingIdentifier = ''): string
    {
        $html = '';
        if (!empty($incomingIdentifier)) {
            $html .= '<div' .
                ' class="treelist-collapse collapse"' .
                ' data-persist-collapse-state="true"' .
                ' data-persist-collapse-state-suffix="lowlevel-configuration-' . $labelHash . '"' .
                ' data-persist-collapse-state-if-state="shown"' .
                ' data-persist-collapse-state-not-if-search="true"' .
                ' id="collapse-list-' . $incomingIdentifier . '">';
        }

        $html .= '<ul class="treelist">';

        foreach ($tree as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $value = $value->name;
            } elseif (is_object($value) && !$value instanceof \Traversable) {
                $value = (array)$value;
            }
            $isValueIterable = is_iterable($value);

            $html .= '<li>';
            $newIdentifier = '';
            if ($isValueIterable && !empty($value)) {
                $newIdentifier = hash('xxh3', $incomingIdentifier . $key);
                $html .= '
                    <typo3-backend-tree-node-toggle
                        class="treelist-control collapsed"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-list-' . $newIdentifier . '"
                        aria-expanded="false">
                    </typo3-backend-tree-node-toggle>';
            }
            $html .= '<span class="treelist-group treelist-group-monospace">';
            $html .= '<span class="treelist-label">' . htmlspecialchars((string)$key) . '</span>';
            if (!$isValueIterable) {
                $html .= ' <span class="treelist-operator">=</span> <span class="treelist-value">' . htmlspecialchars((string)$value) . '</span>';
            }
            if ($isValueIterable && empty($value)) {
                $html .= ' <span class="treelist-operator">=</span>';
            }
            $html .= '</span>';
            if ($isValueIterable && !empty($value)) {
                $html .= $this->renderTree($value, $labelHash, $newIdentifier);
            }
            $html .= '</li>';
        }

        $html .= '</ul>';

        if (!empty($incomingIdentifier)) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @param ProviderInterface[] $providers
     */
    private function addProviderDropDownToDocHeader(ModuleTemplate $view, array $providers, ProviderInterface $selectedProvider): void
    {
        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('tree');
        foreach ($providers as $provider) {
            $menuItem = $menu->makeMenuItem()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('system_config', ['tree' => $provider->getIdentifier()]))
                ->setTitle($provider->getLabel());
            if ($provider === $selectedProvider) {
                $menuItem->setActive(true);
            }
            $menu->addMenuItem($menuItem);
        }
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, ProviderInterface $provider, string $providerIdentifier): void
    {
        $shortcutButton = $view->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
        $shortcutButton
            ->setRouteIdentifier('system_config')
            ->setDisplayName($provider->getLabel())
            ->setArguments(['tree' => $providerIdentifier]);
        $view->getDocHeaderComponent()->getButtonBar()->addButton($shortcutButton);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
