.. include:: /Includes.rst.txt

===========================================================
Important: #80301 - EXT:form - Cleanup / callback migration
===========================================================

See :issue:`80301`

Description
===========

The callback 'onBuildingFinished' is deprecated and will be removed in TYPO3 v9.
--------------------------------------------------------------------------------

Use the new hook 'afterBuildingFinished' instead.

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
     * @return void
     */
    public function afterBuildingFinished(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
    {
    }


This hook will be called for each renderable.


The callback 'beforeRendering' is deprecated and will be removed in TYPO3 v9.
-----------------------------------------------------------------------------

Use the new hook 'beforeRendering' instead.

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the signal:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface $renderable
     * @return void
     */
    public function beforeRendering(\TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime, \TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface $renderable)
    {
    }


This hook will be called for each renderable.


The callback 'onSubmit' is deprecated and will be removed in TYPO3 v9.
----------------------------------------------------------------------

Use the new hook 'afterSubmit' instead.

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
     * @param mixed $elementValue submitted value of the element *before post processing*
     * @param array $requestArguments submitted raw request values
     * @return void
     */
    public function onSubmit(\TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime, \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable, $elementValue, array $requestArguments = [])
    {
        return $elementValue;
    }


This hook will be called for each renderable.


The callback 'initializeFormElement' call the 'initializeFormElement' hook.
---------------------------------------------------------------------------

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
     * @return void
     */
    public function initializeFormElement(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
    {
    }


This enables you to override the 'initializeFormElement' method within your custom implementation class.
If you do not call the parents 'initializeFormElement' then no hook will be thrown.
Furthermore, you can connect to the hook and initialize the generic form elements without defining a
custom implementation to access the 'initializeFormElement' method.
You only need a class which connects to this hook. Then detect the form element you wish to initialize.
This saves you a lot of configuration!


The hook 'beforeRemoveFromParentRenderable' will be called for each renderable.
-------------------------------------------------------------------------------

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
     * @return void
     */
    public function beforeRemoveFromParentRenderable(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
    {
    }


The hook 'afterInitializeCurrentPage' will be called after a page is initialized.
---------------------------------------------------------------------------------

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $currentPage
     * @param null|\TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $lastPage
     * @param mixed $elementValue submitted value of the element *before post processing*
     * @return \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface
     */
    public function afterInitializeCurrentPage(\TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime, \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $currentPage, \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $lastPage = null, array $requestArguments = []): CompositeRenderableInterface
    {
        return $currentPage;
    }


The form manager call the 'beforeFormCreate' hook.
--------------------------------------------------

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param string $formPersistenceIdentifier
     * @param array $formDefinition
     * @return array
     */
    public function beforeFormCreate(string $formPersistenceIdentifier, array $formDefinition): array
    {
        return $formDefinition;
    }


The form manager call the 'beforeFormDuplicate' hook.
-----------------------------------------------------

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param string $formPersistenceIdentifier
     * @param array $formDefinition
     * @return array
     */
    public function beforeFormDuplicate(string $formPersistenceIdentifier, array $formDefinition): array
    {
        return $formDefinition;
    }


The form manager call the 'beforeFormDelete' hook.
--------------------------------------------------

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the signal:

.. code-block:: php

    /**
     * @param string $formPersistenceIdentifier
     * @return void
     */
    public function beforeFormDelete(string $formPersistenceIdentifier)
    {
    }


The form editor call the 'beforeFormSave' hook.
-----------------------------------------------

Connect to the hook:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormSave'][]
        = \VENDOR\YourNamespace\YourClass::class;

Use the hook:

.. code-block:: php

    /**
     * @param string $formPersistenceIdentifier
     * @param array $formDefinition
     * @return array
     */
    public function beforeFormSave(string $formPersistenceIdentifier, array $formDefinition): array
    {
        return $formDefinition;
    }


New form element property: properties.fluidAdditionalAttributes
---------------------------------------------------------------

In order to deal with fluid ViewHelpers 'additionalAttributes' it is necessary to introduce a new configuration
scope "properties.fluidAdditionalAttributes" for each form element.
This configuration property will be used to fill the fluid ViewHelper property "additionalAttributes".


.. index:: Frontend, Backend, PHP-API, Fluid, ext:form
