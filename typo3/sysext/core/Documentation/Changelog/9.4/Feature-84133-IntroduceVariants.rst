.. include:: ../../Includes.txt

====================================
Feature: #84133 - Introduce variants
====================================

See :issue:`84133`

Description
===========


Short Description
-----------------

Variants allow you to change properties of a form element and can be activated based on conditions.

This makes it possible to manipulate form element properties, validator options, and finisher options based on conditions.

This allows you among other things:

* translate form element values depending on the frontend language
* set and remove validators of one form element depending on the value of another form element
* hide entire steps (form pages) depending on the value of a form element
* set finisher options depending on the value of a form element
* hiding a form element in certain finishers and on the summary step

This feature implements variants for frontend rendering and the ability to define variants in form definitions.
The implementation to define variants graphically in the form editor is out of scope of this patchset.


Basics
------

Variants allow you to change properties of form elements, validators, and finishers and are activated by conditions.
They are defined on the form element level either statically in form definitions or created programmatically through an API.

The variants defined within a form definition are automatically applied to the form based on their conditions at runtime.
Programmatically, variants can be applied at any time.

Furthermore, the conditions of a variant can be evaluated programmatically at any time. However, some conditions are only
available at runtime, for example a check for a form element value.

Custom conditions and operators can be added easily.

Only the form element properties listed in a variant are applied to the form element, all other properties are retained.
An exception to this rule are finishers and validators. If finishers or validators are **not** defined within a variant, the
original finishers and validators will be used. If at least one finisher or validator is defined in a variant, the
originally defined finishers or validators are overwritten by the list of finishers and validators of the variant.

Variants defined within a form definition are **all** processed and applied in the order of their condition matches. This means
if variant 1 sets the label of a form element to "X" and variant 2 sets the label to "Y", then variant 2 is applied, i.e. the label
will be "Y".


Variants definition
-------------------

Variants are defined on the form element level. Check the following - incomplete - example:

.. code-block:: yaml

    type: Text
    identifier: text-1
    label: ''
    variants:
      -
        identifier: variant-1
        condition: 'formValues["checkbox-1"] == 1'
        # If the condition matches, the label property of the form element is set to the value 'foo'
        label: foo


As usual :yaml:`identifier` must be a unique name of the variant on the form element level.

Each variant has a single :yaml:`condition` which lets the variants' changes get applied if it matches.

If the :yaml:`condition` of a variant matches, the remaining properties are applied to the form element. In the
aforementioned example the label of the form element :yaml:`text-1` is changed to ``foo`` if the checkbox
:yaml:`checkbox-1` is checked.

The following properties can be overwritten by variants within the topmost element (:yaml:`Form`):

* :yaml:`label`
* :yaml:`renderingOptions`
* :yaml:`finishers`
* :yaml:`rendererClassName`

The following properties can be overwritten by variants within all of the other form elements:

* :yaml:`enabled`
* :yaml:`label`
* :yaml:`defaultValue`
* :yaml:`properties`
* :yaml:`renderingOptions`
* :yaml:`validators`


Conditions
----------

The form framework uses the Symfony component `expression language` to match the conditions. (@see https://symfony.com/doc/4.1/components/expression_language.html)
An expression is a one-liner that returns a boolean value like :yaml:`applicationContext matches "#Production/Local#"`.
Please read https://symfony.com/doc/4.1/components/expression_language/syntax.html to learn more about this topic.
The form framework extends the expression language with some variables which can be used to access form values and environment settings.


``formRuntime`` (object)
^^^^^^^^^^^^^^^^^^^^^^^^

You can access every public method from the :php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime` (@see https://docs.typo3.org/typo3cms/extensions/form/ApiReference/Index.html#typo3-cms-form-domain-model-formruntime).

Example
'''''''

:yaml:`formRuntime.getIdentifier() == "test"`.


``formValues`` (array)
^^^^^^^^^^^^^^^^^^^^^^

:yaml:`formValues` holds all of the submitted form element values. Each key within this array represents a form element identifier.

Example
'''''''

:yaml:`formValues["text-1"] == "yes"`.


``stepIdentifier`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`stepIdentifier` is set to the :yaml:`identifier` of the current step.

Example
'''''''

:yaml:`stepIdentifier == "page-1"`.


``stepType`` (string)
^^^^^^^^^^^^^^^^^^^^^

:yaml:`stepType` is set to the :yaml:`type` of the current step.

Example
'''''''

:yaml:`stepType == "SummaryPage"`.


``finisherIdentifier`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`finisherIdentifier` is set to the :yaml:`identifier` of the current finisher or an empty string (while no finishers are executed).

Example
'''''''

:yaml:`finisherIdentifier == "EmailToSender"`.


``siteLanguage`` (object)
^^^^^^^^^^^^^^^^^^^^^^^^^

You can access every public method from :php:`\TYPO3\CMS\Core\Site\Entity\SiteLanguage`.
The most needed ones are probably:

* getLanguageId() / Aka sys_language_uid.
* getLocale() / The language locale. Something like 'en_US.UTF-8'.
* getTypo3Language() / The language key for XLF files. Something like 'de' or 'default'.
* getTwoLetterIsoCode() / Returns the ISO-639-1 language ISO code. Something like 'de'.

Example
'''''''

:yaml:`siteLanguage("locale") == "de_DE"`.


``applicationContext`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`applicationContext` is set to the application context (@see GeneralUtility::getApplicationContext()).

Example
'''''''

:yaml:`applicationContext matches "#Production/Local#"`.


``contentObject`` (array)
^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`contentObject` is set to the data of the current content object or to an empty array if no content object is available.

Example
'''''''

:yaml:`contentObject["pid"] in [23, 42]`.


Working with variants programmatically
--------------------------------------

Create a variant with conditions through the PHP API:

.. code-block:: php

    /** @var TYPO3\CMS\Form\Domain\Model\Renderable\RenderableVariantInterface $variant */
    $variant = $formElement->createVariant([
        'identifier' => 'variant-1',
        'condition' => 'formValues["checkbox-1"] == 1',
        'label' => 'foo',
    ]);

Get all variants of a form element:

.. code-block:: php

    /** @var TYPO3\CMS\Form\Domain\Model\Renderable\RenderableVariantInterface[] $variants */
    $variants = $formElement->getVariants();

Apply a variant to a form element regardless of its defined conditions:

.. code-block:: php

    $formElement->applyVariant($variant);


Examples
--------

Translate form element values depending on the frontend language:

.. code-block:: yaml

    type: Form
    identifier: test
    prototypeName: standard
    label: DE
    renderingOptions:
      submitButtonLabel: Abschicken
    variants:
      -
        identifier: language-variant-1
        condition: 'siteLanguage("locale") == "en_US.UTF-8"'
        label: EN
        renderingOptions:
          submitButtonLabel: Submit
    renderables:
      -
        type: Page
        identifier: page-1
        label: DE
        renderingOptions:
          previousButtonLabel: 'zurück'
          nextButtonLabel: 'weiter'
        variants:
          -
            identifier: language-variant-1
            condition: 'siteLanguage("locale") == "en_US.UTF-8"'
            label: EN
            renderingOptions:
              previousButtonLabel: 'Previous step'
              nextButtonLabel: 'Next step'
        renderables:
          -
            type: Text
            identifier: text-1
            label: DE
            properties:
              fluidAdditionalAttributes:
                placeholder: Platzhalter
            variants:
              -
                identifier: language-variant-1
                condition: 'siteLanguage("locale") == "en_US.UTF-8"'
                label: EN
                properties:
                  fluidAdditionalAttributes:
                    placeholder: Placeholder

Set validators of one form element depending on the value of another form element:

.. code-block:: yaml


    type: Form
    identifier: test
    label: test
    prototypeName: standard
    renderables:
      -
        type: Page
        identifier: page-1
        label: Step
        renderables:
          -
            defaultValue: ''
            type: Text
            identifier: text-1
            label: 'Email address'
            variants:
              -
                identifier: variant-1
                condition: 'formValues["checkbox-1"] == 1'
                properties:
                  fluidAdditionalAttributes:
                    required: 'required'
                validators:
                  -
                    identifier: NotEmpty
                  -
                    identifier: EmailAddress
          -
            type: Checkbox
            identifier: checkbox-1
            label: 'Subscribe to newsletter'

Hide entire steps depending on the value of a form element:

.. code-block:: yaml

    type: Form
    identifier: test
    prototypeName: standard
    label: Test
    renderables:
      -
        type: Page
        identifier: page-1
        label: 'Page 1'
        renderables:
          -
            type: Text
            identifier: text-1
            label: 'Text 1'
          -
            type: Checkbox
            identifier: checkbox-1
            label: 'Skip page 2'
            variants:
              -
                identifier: hide-1
                condition: 'stepType == "SummaryPage"'
                renderingOptions:
                  enabled: false
      -
        type: Page
        identifier: page-2
        label: 'Page 2'
        variants:
          -
            identifier: variant-1
            condition: 'formValues["checkbox-1"] == 1'
            renderingOptions:
              enabled: false
        renderables:
          -
            type: Text
            identifier: text-2
            label: 'Text 2'
      -
        type: SummaryPage
        identifier: summarypage-1
        label: 'Summary step'

Set finisher values depending on the application context:

.. code-block:: yaml

    type: Form
    identifier: test
    prototypeName: standard
    label: Test
    renderingOptions:
      submitButtonLabel: Submit
    finishers:
      -
        identifier: Confirmation
        options:
          message: 'Thank you'
    variants:
      -
        identifier: variant-1
        condition: 'applicationContext matches "#Production/Local#"'
        finishers:
          -
            identifier: Confirmation
            options:
              message: 'ouy knahT'
    renderables:
      -
        type: Page
        identifier: page-1
        label: 'Page 1'
        renderingOptions:
          previousButtonLabel: 'Previous step'
          nextButtonLabel: 'Next step'

Hide a form element in certain finishers and on the summary step:

.. code-block:: yaml

    type: Form
    identifier: test
    prototypeName: standard
    label: Test
    finishers:
      -
        identifier: EmailToReceiver
        options:
          subject: Testmail
          recipientAddress: tritum@example.org
          recipientName: 'Test'
          senderAddress: tritum@example.org
          senderName: tritum@example.org
    renderables:
      -
        type: Page
        identifier: page-1
        label: 'Page 1'
        renderables:
          -
            type: Text
            identifier: text-1
            label: 'Text 1'
            variants:
              -
                identifier: hide-1
                renderingOptions:
                  enabled: false
                condition: 'stepType == "SummaryPage" || finisherIdentifier in ["EmailToSender", "EmailToReceiver"]'
          -
            type: Text
            identifier: text-2
            label: 'Text 2'
      -
        type: SummaryPage
        identifier: summarypage-1
        label: 'Summary step'


Adding own expression language provider
---------------------------------------

If you need to extend the expression language with custom functions you can extend it. For more
information @see https://symfony.com/doc/4.1/components/expression_language/extending.html#using-expression-providers.

First of all, you have to register the expression language provider within the form setup:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              conditionContextDefinition:
                expressionLanguageProvider:
                  MyCustomExpressionLanguageProvider:
                    implementationClassName: '\Vendor\MyExtension\CustomExpressionLanguageProvider'

Your expression language provider must implement :php`Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface`.


Adding own expression language variables
----------------------------------------

If you need to add custom variables to the expression language you can extend it.
Then the variables are ready to be checked in conditions.

First of all, you have to register the variable provider within the form setup:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              conditionContextDefinition:
                expressionLanguageVariableProvider:
                  MyCustomExpressionLanguageVariableProvider:
                    implementationClassName: '\Vendor\MyExtension\CustomExpressionLanguageVariableProvider'

Your expression language variable provider must implement :php`TYPO3\CMS\Form\Domain\Condition\ExpressionLanguageVariableProviderInterface`.


.. index:: Frontend, ext:form
