..  include:: /Includes.rst.txt

..  _feature-93789-1794239825:

=============================================================
Feature: #93789 - Access the page context of backend requests
=============================================================

See :issue:`93789`

Description
===========

Backend modules and routes working with a page can now rely on the
request attribute `pageContext`, which carries a
:php:`\TYPO3\CMS\Backend\Context\PageContext` object. Before, every
extension had to find out on its own which page a backend request is
about.

The attribute is added to the request of

*   every backend module using the page tree navigation component
    (`@typo3/backend/tree/page-tree-element`), either directly or
    inherited from its parent module, for example most of the modules
    below :guilabel:`Content`, and
*   every backend route with the option `requestPageContext` set to
    `true`, for example the record editing form.

The page is determined from the `id` argument of the request. Without
it, the page is taken from an `edit` statement (the page of the edited
record, or the target of a new record) or from a `cmd` statement (the
page of a deleted record, or the target of a copy or move command).
When no page can be determined, the context is created for page `0`.
The attribute is not set when no backend user is logged in, or when
the context cannot be created, so check it before using it.

The :php:`PageContext` object provides

*   `pageId`: the requested page ID, even if the user has no access,
*   `pageRecord`: the page record, or `null` if the user has no access,
*   `site`: the site of the page,
*   `pageAncestry`: the :php:`\TYPO3\CMS\Core\Domain\PageAncestry` of
    the page in the workspace of the user, or `null` if the user has no
    access,
*   `pageTsConfig`: the page TSconfig of the page,
*   `pagePermissions`: the permissions of the user on the page,
*   `selectedLanguageIds` and `languageInformation`: the languages
    selected in the module and the translations of the page, as
    :php:`\TYPO3\CMS\Backend\Domain\Model\Language\PageLanguageInformation`,

as well as the methods :php:`isAccessible()`,
:php:`getPrimaryLanguageId()`, :php:`hasMultipleLanguagesSelected()`,
:php:`isLanguageSelected()`, :php:`isDefaultLanguageSelected()`,
:php:`getPageTitle()` and :php:`getModuleTsConfig()`.

:php:`PageContext`, :php:`PageLanguageInformation`,
:php:`LanguageItem` and :php:`LanguageStatus` are now public API. Their
constructors remain internal: the objects are created by TYPO3 Core.

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyModuleController.php

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Backend\Context\PageContext;

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $pageContext = $request->getAttribute('pageContext');
        if (!$pageContext instanceof PageContext) {
            throw new \RuntimeException('Page context not available', 1794239826);
        }
        if (!$pageContext->isAccessible()) {
            // Render a "no access" view
        }
        $pageId = $pageContext->pageId;
        $moduleTsConfig = $pageContext->getModuleTsConfig('my_module');
        // ...
    }

A route that is not part of a module with the page tree requests the
page context with an option:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Routes.php

    return [
        'my_record_action' => [
            'path' => '/my-extension/record-action',
            'target' => \MyVendor\MyExtension\Controller\RecordActionController::class . '::handleRequest',
            'options' => [
                'requestPageContext' => true,
            ],
        ],
    ];

Impact
======

Extensions can read the current page, its site, its rootline, its page
TSconfig and the permissions of the user from the `pageContext`
request attribute, instead of evaluating the `id` argument and
fetching this information themselves.

..  index:: Backend, PHP-API, ext:backend
