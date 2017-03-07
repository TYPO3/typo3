.. include:: ../../Includes.txt

===========================================================
Important: #80301 - EXT:form - Cleanup / callback migration
===========================================================

See :issue:`80301`

Description
===========

The callback 'onBuildingFinished' is deprecated and will be removed in TYPO3 v9.
--------------------------------------------------------------------------------

Use the new signal slot 'onBuildingFinished' instead.

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Domain\Runtime\FormRuntime::class,
        'onBuildingFinished',
        \VENDOR\YourNamespace\YourClass::class,
        'onBuildingFinished'
    );

Use the signal:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
     * @return void
     */
    public function onBuildingFinished(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
    {
    }


This signal will be dispatched for each renderable.


The callback 'beforeRendering' is deprecated and will be removed in TYPO3 v9.
-----------------------------------------------------------------------------

Use the new signal slot 'beforeRendering' instead.

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Domain\Runtime\FormRuntime::class,
        'beforeRendering',
        \VENDOR\YourNamespace\YourClass::class,
        'beforeRendering'
    );

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


This signal will be dispatched for each renderable.


The callback 'onSubmit' is deprecated and will be removed in TYPO3 v9.
----------------------------------------------------------------------

Use the new signal slot 'onSubmit' instead.

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Domain\Runtime\FormRuntime::class,
        'onSubmit',
        \VENDOR\YourNamespace\YourClass::class,
        'onSubmit'
    );

Use the signal:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
     * @param mixed &$elementValue submitted value of the element *before post processing*
     * @param array $requestArguments submitted raw request values
     * @return void
     */
    public function onSubmit(\TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime, \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable, &$elementValue, array $requestArguments = [])
    {
    }


This signal will be dispatched for each renderable.


The callback 'initializeFormElement' dispatches the 'initializeFormElement' signal.
-----------------------------------------------------------------------------------

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable::class,
        'initializeFormElement',
        \VENDOR\YourNamespace\YourClass::class,
        'initializeFormElement'
    );

Use the signal:

.. code-block:: php

    /**
     * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
     * @return void
     */
    public function initializeFormElement(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
    {
    }


This enables you to override the 'initializeFormElement' method within your custom implementation class.
If you do not call the parents 'initializeFormElement' then no signal will be thrown.
Furthermore, you can connect to the signal and initialize the generic form elements without defining a
custom implementaion to access the 'initializeFormElement' method.
You only need a class which connects to this signal. Then detect the form element you wish to initialize.
This saves you a lot of configuration!


The form manager dispatches the 'beforeFormCreate' signal.
----------------------------------------------------------

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Controller\FormManagerController::class,
        'beforeFormCreate',
        \VENDOR\YourNamespace\YourClass::class,
        'beforeFormCreate'
    );

Use the signal:

.. code-block:: php

    /**
     * @string $formPersistenceIdentifier
     * @array $formDefinition
     * @return void
     */
    public function beforeFormCreate(string $formPersistenceIdentifier, array &$formDefinition)
    {
    }


The form manager dispatches the 'beforeFormDuplicate' signal.
-------------------------------------------------------------

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Controller\FormManagerController::class,
        'beforeFormDuplicate',
        \VENDOR\YourNamespace\YourClass::class,
        'beforeFormDuplicate'
    );

Use the signal:

.. code-block:: php

    /**
     * @string $formPersistenceIdentifier
     * @array $formDefinition
     * @return void
     */
    public function beforeFormDuplicate(string $formPersistenceIdentifier, array &$formDefinition)
    {
    }


The form manager dispatches the 'beforeFormDelete' signal.
----------------------------------------------------------

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Controller\FormManagerController::class,
        'beforeFormDelete',
        \VENDOR\YourNamespace\YourClass::class,
        'beforeFormDelete'
    );

Use the signal:

.. code-block:: php

    /**
     * @string $formPersistenceIdentifier
     * @return void
     */
    public function beforeFormDelete(string $formPersistenceIdentifier)
    {
    }

The form editor dispatches the 'beforeFormSave' signal.
-------------------------------------------------------

Connect to the signal:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)->connect(
        \TYPO3\CMS\Form\Controller\FormEditorController::class,
        'beforeFormSave',
        \VENDOR\YourNamespace\YourClass::class,
        'beforeFormSave'
    );

Use the signal:

.. code-block:: php

    /**
     * @string $formPersistenceIdentifier
     * @array $formDefinition
     * @return void
     */
    public function beforeFormSave(string $formPersistenceIdentifier, array &$formDefinition)
    {
    }


New form element property: properties.fluidAdditionalAttributes
---------------------------------------------------------------

To deal with fluid ViewHelpers 'additionalAttributes' it is necessary to introduce a new configuration scope "properties.fluidAdditionalAttributes" for each form element.
This configuration property will be used to fill the fluid ViewHelper property "additionalAttributes".


.. index:: Frontend, Backend, ext:form
