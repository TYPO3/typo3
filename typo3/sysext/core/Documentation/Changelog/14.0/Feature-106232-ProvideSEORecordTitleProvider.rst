..  include:: /Includes.rst.txt

..  _feature-106232-1740166029:

====================================================
Feature: #106232 - Provide SEO record title provider
====================================================

See :issue:`106232`

Description
===========

The class :php:`\TYPO3\CMS\Seo\PageTitle\RecordTitleProvider`
is a new page title provider with the identifier `recordTitle` which is called before
:php:`\TYPO3\CMS\Seo\PageTitle\SeoTitlePageTitleProvider` with the TypoScript
identifier `seo`.

This provider can be used by 3rd party extensions to set the page title.

..  code-block:: php
    :caption: my_extension/Classes/Controller/ItemController.php

    use MyVendor\MyExtension\Domain\Model\Item;
    use Psr\Http\Message\ResponseInterface;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
    use TYPO3\CMS\Seo\PageTitle\RecordTitleProvider;

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

Ease the life of extension developers by providing a dedicated provider
instead of forcing them to provide a provider in every extension.

..  index:: Frontend, ext:seo
