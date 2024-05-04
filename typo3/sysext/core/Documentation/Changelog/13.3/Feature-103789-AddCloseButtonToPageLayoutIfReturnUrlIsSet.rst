.. include:: /Includes.rst.txt

.. _feature-103789-1714805317:

=========================================================================
Feature: #103789 - Add "close"-button to page layout, if returnUrl is set
=========================================================================

See :issue:`103789`

Description
===========

A "close"-button is now displayed in the page module, if the :php:`returnUrl`
argument is set. When this button is clicked, the previous module
leading to the page module (or a custom link defined in :php:`returnUrl`) will be displayed
again.

In order to utilize this, backend module links set in extensions must pass the :php:`returnUrl`
argument. If :php:`returnUrl` is not set, the "close"-button will not be displayed.

Examples
--------

Here is an example, using the Fluid :html:`<be:moduleLink>` ViewHelper:

..  code-block:: html
    :caption: Fluid example

    <a href="{be:moduleLink(route:'web_layout', arguments:'{id:pageUid, returnUrl: returnUrl}')}"
       class="btn btn-default"
       title="{f:translate(key: 'LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:title')}">
        <core:icon identifier="actions-document" size="small"/>
    </a>

The behaviour is similar to the :html:`<be:uri.editRecord>` ViewHelper,
where setting the :html:`returnUrl` argument will also cause a "close"-button to
be displayed.

.. important::

    When using the :html:`<be:uri.editRecord>` ViewHelper, :html:`returnUrl` is
    passed directly as argument. However, using :html:`<be:moduleLink>`, the
    :html:`returnUrl` argument must be passed as an additional parameter via the Fluid
    ViewHelper's argument :html:`arguments` or :html:`query`.

The :html:`returnUrl` should usually return to the calling (originating) module.

You can build the :html:`returnUrl` with the Fluid ViewHelper :html:`be:uri`:

..  code-block:: html
    :caption: Fluid example for building returnUrl to module "linkvalidator"

    <f:be.uri route="web_linkvalidator" parameters="{id: pageUid}"/>

Here is an example for building the :html:`returnUrl` via PHP:

..  code-block:: php
    :caption: Backend module controller

    use TYPO3\CMS\Backend\Routing\UriBuilder;

    public function __construct(
        protected readonly UriBuilder $uriBuilder
    ) {}

    protected function generateModuleUri(array $parameters = []): string
    {
        return $this->uriBuilder->buildUriFromRoute('web_linkvalidator',  $parameters);
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // ...
        $this->view->assign('returnUrl', $this->generateModuleUri(['pageUid' => $this->id]));
        // ...
    }

Impact
======

The change has no impact, unless the functionality is being used. Extension
authors can make use of the new functionality to also conveniently link back to an originating
or custom module for a streamlined linear backend user-experience.

.. index:: Backend, ext:backend
