..  include:: /Includes.rst.txt

..  _feature-106232-1740166029:

====================================================
Feature: #106232 - Provide record title tag provider
====================================================

See :issue:`106232`

Description
===========

The class :php:`\TYPO3\CMS\Core\PageTitle\RecordTitleProvider`
introduces a new page title provider with the identifier `recordTitle`.
It is executed before the :php-short:`\TYPO3\CMS\Core\PageTitle\SeoTitlePageTitleProvider`,
which uses the TypoScript identifier `seo`.

This provider can be used by third-party extensions to set the page title
programmatically.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/ItemController.php

    use MyVendor\MyExtension\Domain\Model\Item;
    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
    use TYPO3\CMS\Core\PageTitle\RecordTitleProvider;

    final class ItemController extends ActionController
    {
        public function __construct(
            private readonly RecordTitleProvider $recordTitleProvider
        ) {
        }

        public function showAction(Item $item): ResponseInterface
        {
            $this->recordTitleProvider->setTitle($item->getTitle());
            $this->view->assign('item', $item);
            return $this->htmlResponse();
        }
    }

Impact
======

A dedicated provider is now available for extensions to set page titles
without needing to implement their own custom provider.

..  index:: Frontend, ext:core
