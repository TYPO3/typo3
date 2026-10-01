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

namespace TYPO3\CMS\Dashboard\Widgets;

use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Settings\SettingDefinition;

/**
 * Concrete List Widget implementation
 *
 * The widget will show a simple list with items provided by a data provider. You can add a button to the widget by
 * defining a button provider.
 *
 * A data provider implementing PaginatedListDataProviderInterface makes the widget render a paginator
 * in its footer. Editors can then configure the number of items per page in the widget settings.
 *
 * The following options are available during registration:
 * - itemsPerPage   int     default: 10         Number of items per page. Only used for paginated data providers.
 *                                              If set, editors cannot change the value.
 *
 * @see ListDataProviderInterface
 * @see PaginatedListDataProviderInterface
 * @see ButtonProviderInterface
 */
class ListWidget implements WidgetRendererInterface
{
    private const DEFAULT_ITEMS_PER_PAGE = 10;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly ListDataProviderInterface|PaginatedListDataProviderInterface $dataProvider,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
        /** @var array{refreshAvailable?: bool, itemsPerPage?: int} */
        private readonly array $options = [],
    ) {}

    /**
     * @return SettingDefinition[]
     */
    public function getSettingsDefinitions(): array
    {
        if (!$this->dataProvider instanceof PaginatedListDataProviderInterface) {
            return [];
        }
        return [
            new SettingDefinition(
                key: 'itemsPerPage',
                type: 'int',
                default: (int)($this->options['itemsPerPage'] ?? self::DEFAULT_ITEMS_PER_PAGE),
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.pagination.setting.itemsPerPage.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.pagination.setting.itemsPerPage.description',
                readonly: array_key_exists('itemsPerPage', $this->options),
                options: [
                    'min' => 1,
                    'max' => 100,
                ],
            ),
        ];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $paginator = $this->getPaginator($context);
        $view = $this->backendViewFactory->create($context->request);
        $view->assignMultiple([
            'items' => $paginator !== null ? iterator_to_array($paginator->getPaginatedItems(), false) : $this->getItems(),
            'paginator' => $paginator,
            'pagination' => $paginator !== null ? new SimplePagination($paginator) : null,
            'options' => $this->options,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
        ]);
        return new WidgetResult(
            content: $view->render('Widget/ListWidget'),
            refreshable: $this->options['refreshAvailable'] ?? false,
        );
    }

    protected function getItems(): array
    {
        return $this->dataProvider instanceof ListDataProviderInterface ? $this->dataProvider->getItems() : [];
    }

    protected function getPaginator(WidgetContext $context): ?PaginatorInterface
    {
        if (!$this->dataProvider instanceof PaginatedListDataProviderInterface) {
            return null;
        }
        $currentPage = max(1, (int)($context->request->getQueryParams()['page'] ?? 1));
        $itemsPerPage = $context->settings->has('itemsPerPage')
            ? max(1, (int)$context->settings->get('itemsPerPage'))
            : (int)($this->options['itemsPerPage'] ?? self::DEFAULT_ITEMS_PER_PAGE);
        return $this->dataProvider->getPaginator($currentPage, $itemsPerPage);
    }
}
