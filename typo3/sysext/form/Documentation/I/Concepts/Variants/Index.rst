.. include:: /Includes.rst.txt


.. _concepts-variants:

Variants
========


.. _concepts-variants-basics:

Basics
------

Variants allow you to change properties of form elements, validators,
and finishers and are activated by conditions. This allows you among
other things:

* translating form element values depending on the frontend language
* setting and removing validators of one form element depending on the
  value of another form element
* hiding entire steps (form pages) depending on the value of a form
  element
* setting finisher options depending on the value of a form element
* hiding a form element in certain finishers and on the summary step

Variants are defined on the form element level either statically in
form definitions or created programmatically through an API. The
variants defined within a form definition are automatically applied to
the form based on their conditions at runtime. Programmatically,
variants can be applied at any time.

Furthermore, conditions of a variant can be evaluated programmatically
at any time. However, some conditions are only available at runtime,
for example a check for a form element value.

Custom conditions and operators can be added easily.

Only the form element properties listed in a variant are applied to the
form element, all other properties are retained. An exception to this
rule are finishers and validators. If finishers or validators are
**not** defined within a variant, the original finishers and validators
will be used. If at least one finisher or validator is defined in a
variant, the originally defined finishers or validators are overwritten
by the list of finishers and validators of the variant.

Variants defined within a form definition are **all** processed and
applied in the order of their matching conditions. This means if
variant 1 sets the label of a form element to "X" and variant 2 sets
the label to "Y", then variant 2 is applied, i.e. the label will be "Y".

.. note::
   At the current state it is **not** possible to define variants in
   the UI of the form editor.


.. _concepts-variants-enabled-property:

Rendering option ``enabled``
----------------------------

The rendering option :yaml:`enabled` is available for all finishers and
all form elements - except the root form element and the first form
page. The option accepts a boolean value (:yaml:`true` or :yaml:`false`).

Setting :yaml:`enabled: true` for a form element renders it in the
frontend and enables processing of its value including property mapping
and validation. Setting :yaml:`enabled: false` disables the form
element in the frontend. All form elements and finishers except the root form element and the first form page can be enabled
or disabled.

Setting :yaml:`enabled: true` for a finisher executes it when
submitting forms. Setting :yaml:`enabled: false` skips the finisher.

By default, :yaml:`enabled` is set to :yaml:`true`.

See :ref:`examples<concepts-variants-examples-hide-form-elements>`
below to learn more about using this rendering option.


.. _concepts-variants-definition:

Definition of variants
----------------------

Variants are defined on the form element level. Check the following -
incomplete - example:

.. code-block:: yaml

   type: Text
   identifier: text-1
   label: Foo
   variants:
     -
       identifier: variant-1
       condition: 'formValues["checkbox-1"] == 1'
       # If the condition matches, the label property of the form
       # element is set to the value 'Bar'
       label: Bar


As usual, :yaml:`identifier` must be a unique name of the variant on
the form element level.

Each variant has a single :yaml:`condition` which applies the variants'
changes as soon as the condition matches. In addition, the remaining
properties are applied to the form element as well. In the
aforementioned example the label of the form element :yaml:`text-1` is
changed to ``Bar`` if the checkbox :yaml:`checkbox-1` is checked.

The following properties can be overwritten by variants within the
topmost element (:yaml:`Form`):

* :yaml:`label`
* :yaml:`renderingOptions`
* :yaml:`finishers`
* :yaml:`rendererClassName`

The following properties can be overwritten by variants within all of
the other form elements:

* :yaml:`enabled`
* :yaml:`label`
* :yaml:`defaultValue`
* :yaml:`properties`
* :yaml:`renderingOptions`
* :yaml:`validators`

.. note::
   To selectively unset list items in variants like select options the special value :code:`__UNSET` can be used as value for the item to remove.

.. _concepts-variants-conditions:

Conditions
----------

The form framework uses the Symfony component `expression language <https://symfony.com/doc/4.1/components/expression_language.html>`_.
Here, an expression is a one-liner that returns a boolean value like
:yaml:`applicationContext matches "#Production/Local#"`. Please check
the `Symfony docs <https://symfony.com/doc/4.1/components/expression_language/syntax.html>`_
to learn more about this topic. The form framework extends the
expression language with some variables which can be used to access
form values and environment settings.


``formRuntime`` (object)
^^^^^^^^^^^^^^^^^^^^^^^^

You can access every public method from the :php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime`,
learn more :ref:`here<apireference-frontendrendering-programmatically-apimethods-formruntime>`.

For example:

:yaml:`formRuntime.getIdentifier() == "test"`.


``formValues`` (array)
^^^^^^^^^^^^^^^^^^^^^^

:yaml:`formValues` holds all the submitted form element values. Each
key within this array represents a form element identifier.

For example:

:yaml:`formValues["text-1"] == "yes"`.


``stepIdentifier`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`stepIdentifier` is set to the :yaml:`identifier` of the current
step.

For example:

:yaml:`stepIdentifier == "page-1"`.


``stepType`` (string)
^^^^^^^^^^^^^^^^^^^^^

:yaml:`stepType` is set to the :yaml:`type` of the current step.

For example:

:yaml:`stepType == "SummaryPage"`.


``finisherIdentifier`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`finisherIdentifier` is set to the :yaml:`identifier` of the
current finisher or an empty string (while no finishers are executed).

For example:

:yaml:`finisherIdentifier == "EmailToSender"`.


``siteLanguage`` (object)
^^^^^^^^^^^^^^^^^^^^^^^^^

You can access every public method from :php:`\TYPO3\CMS\Core\Site\Entity\SiteLanguage`.
The most needed ones are for sure:

* getLanguageId() / Aka sys_language_uid.
* getLocale() / The language locale. Something like 'en_US.UTF-8'.
* getTypo3Language() / The language key for XLF files. Something like
  'de' or 'default'.
* getTwoLetterIsoCode() / Returns the ISO-639-1 language ISO code.
  Something like 'de'.

For example:

:yaml:`siteLanguage("locale") == "de_DE"`.


``applicationContext`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`applicationContext` is set to the application context
(@see GeneralUtility::getApplicationContext()).

For example:

:yaml:`applicationContext matches "#Production/Local#"`.


``contentObject`` (array)
^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`contentObject` is set to the data of the current content object
or to an empty array if no content object is available.

For example:

:yaml:`contentObject["pid"] in [23, 42]`.


.. _concepts-variants-programmatically:

Working with variants programmatically
--------------------------------------

Create a variant with conditions through the PHP API::

   /** @var TYPO3\CMS\Form\Domain\Model\Renderable\RenderableVariantInterface $variant */
   $variant = $formElement->createVariant([
       'identifier' => 'variant-1',
       'condition' => 'formValues["checkbox-1"] == 1',
       'label' => 'foo',
   ]);


Get all variants of a form element::

   /** @var TYPO3\CMS\Form\Domain\Model\Renderable\RenderableVariantInterface[] $variants */
   $variants = $formElement->getVariants();


Apply a variant to a form element regardless of its defined conditions::

   $formElement->applyVariant($variant);


.. _concepts-variants-examples:

Examples
--------

Here are some complex examples to show you the possibilities of the
form framework.


.. _concepts-variants-examples-translation:

Translation of form elements
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In this example form elements are translated differently depending on
the frontend language.

.. code-block:: yaml

   type: Form
   prototypeName: standard
   identifier: contact-form
   label: Kontaktformular
   renderingOptions:
     submitButtonLabel: Senden
   variants:
     -
       identifier: language-variant-1
       condition: 'siteLanguage("locale") == "en_US.UTF-8"'
       label: Contact form
       renderingOptions:
         submitButtonLabel: Submit
   renderables:
     -
       type: Page
       identifier: page-1
       label: Kontaktdaten
       renderingOptions:
         previousButtonLabel: zurück
         nextButtonLabel: weiter
       variants:
         -
           identifier: language-variant-1
           condition: 'siteLanguage("locale") == "en_US.UTF-8"'
           label: Contact data
           renderingOptions:
             previousButtonLabel: Previous step
             nextButtonLabel: Next step
       renderables:
         -
           type: Text
           identifier: text-1
           label: Vollständiger Name
           properties:
             fluidAdditionalAttributes:
               placeholder: Ihre vollständiger Name
           variants:
             -
               identifier: language-variant-1
               condition: 'siteLanguage("locale") == "en_US.UTF-8"'
               label: Full name
               properties:
                 fluidAdditionalAttributes:
                   placeholder: Your full name


.. _concepts-variants-examples-validation:

Adding validators dynamically
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In this example a bunch of validators are added to the field
:yaml:`email-address` depending on the value of the form element
:yaml:`checkbox-1`.

.. code-block:: yaml

   type: Form
   prototypeName: standard
   identifier: newsletter-subscription
   label: Newsletter Subscription
   renderables:
     -
       type: Page
       identifier: page-1
       label: General data
       renderables:
         -
           type: Text
           identifier: email-address
           label: Email address
           defaultValue:
           variants:
             -
               identifier: validation-1
               condition: 'formValues["checkbox-1"] == 1'
               properties:
                 fluidAdditionalAttributes:
                   required: required
               validators:
                 -
                   identifier: NotEmpty
                 -
                   identifier: EmailAddress
         -
           type: Checkbox
           identifier: checkbox-1
           label: Check this and email will be mandatory


.. _concepts-variants-examples-hide-form-elements:

Hide form elements
^^^^^^^^^^^^^^^^^^

In this extensive example the form element :yaml:`email-address` has
been enabled explicitly but it is fine to leave this out since this is
the default state. The form element :yaml:`text-3` has been disabled
completely, for example to temporarily remove it from the form. The
field :yaml:`text-1` is hidden in all finishers and on the summary step.
The :yaml:`EmailToSender` finisher takes the fact into account that
finishers can refer to form values. It is only enabled if the form
element :yaml:`checkbox-1` has been activated by the user. Otherwise,
the finisher is skipped.

.. code-block:: yaml

   type: Form
   prototypeName: standard
   identifier: hidden-field-form
   label: Hidden field form
   finishers:
     -
       identifier: EmailToReceiver
       options:
         subject: Yes, I am ready
         recipients:
           your.company@example.com: 'Your Company name'
         senderAddress: tritum@example.org
         senderName: tritum@example.org
     -
       identifier: EmailToSender
       options:
         subject: This is a copy of the form data
         recipients:
           {email-address}: '{name}'
         senderAddress: tritum@example.org
         senderName: tritum@example.org
         renderingOptions:
           enabled: '{checkbox-1}'
   renderables:
     -
       type: Page
       identifier: page-1
       label: General data
       renderables:
         -
           type: Text
           identifier: text-1
           label: A field hidden on confirmation step and in all mails (finishers)
           variants:
             -
               identifier: hide-1
               renderingOptions:
                 enabled: false
               condition: 'stepType == "SummaryPage" || finisherIdentifier in ["EmailToSender", "EmailToReceiver"]'
         -
           type: Text
           identifier: email-address
           label: Email address
           properties:
             fluidAdditionalAttributes:
               required: required
           renderingOptions:
             enabled: true
         -
           type: Text
           identifier: text-3
           label: A temporarily disabled field
           renderingOptions:
             enabled: false
         -
           type: Checkbox
           identifier: checkbox-1
           label: Check this and the sender gets an email
     -
       type: SummaryPage
       identifier: summarypage-1
       label: Confirmation


.. _concepts-variants-examples-hide-steps:

Hide steps
^^^^^^^^^^

In this example the second step :yaml:`page-2` is disabled if the field
:yaml:`checkbox-1` is checked. Furthermore, the form element
:yaml:`checkbox-1` is disabled on the summary step.

.. code-block:: yaml

   type: Form
   prototypeName: standard
   identifier: multi-step-form
   label: Muli step form
   renderables:
     -
       type: Page
       identifier: page-1
       label: First step
       renderables:
         -
           type: Text
           identifier: text-1
           label: A field
         -
           type: Checkbox
           identifier: checkbox-1
           label: Check this and the next step will be skipped
           variants:
             -
               identifier: variant-1
               condition: 'stepType == "SummaryPage"'
               renderingOptions:
                 enabled: false
     -
       type: Page
       identifier: page-2
       label: Second step
       variants:
         -
           identifier: variant-2
           condition: 'formValues["checkbox-1"] == 1'
           renderingOptions:
             enabled: false
       renderables:
         -
           type: Text
           identifier: text-2
           label: Another field
     -
       type: SummaryPage
       identifier: summarypage-1
       label: Confirmation


.. _concepts-variants-examples-finisher:

Set finisher values dynamically
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In this example finisher values are set differently depending on the
application context.

.. code-block:: yaml

   type: Form
   prototypeName: standard
   identifier: finisher-condition-example
   label: Finishers under condition
   finishers:
     -
       identifier: Confirmation
       options:
         message: I am NOT a local environment.
   variants:
     -
       identifier: variant-1
       condition: 'applicationContext matches "#Production/Local#"'
       finishers:
         -
           identifier: Confirmation
           options:
             message: I am a local environment.
   renderables:
     -
       type: Page
       identifier: page-1
       label: General data
       renderables:
         -
           type: Text
           identifier: text-1
           label: A field


.. _concepts-variants-examples-remove-options:

Remove select options
^^^^^^^^^^^^^^^^^^^^^

In this example a select option is removed for a specific locale.

.. code-block:: yaml

   type: Form
   prototypeName: standard
   identifier: option-remove-example
   label: Options removed under condition
   renderables:
     -
       type: Page
       identifier: page-1
       label: Step
       renderables:
         -
           identifier: salutation
           type: SingleSelect
           label: Salutation
           properties:
             options:
               '': '---'
               mr: Mr.
               mrs: Mrs.
               miss: Miss
           defaultValue: ''
           variants:
             -
               identifier: salutation-variant
               condition: 'siteLanguage("locale") == "zh_CN.utf-8"'
               properties:
                 options:
                   miss: __UNSET


.. _concepts-variants-custom-language-providers:

Adding own expression language providers
----------------------------------------

If you need to extend the expression language with custom functions you
can extend it. For more information check the official `docs <https://symfony.com/doc/5.4/components/expression_language/extending.html#using-expression-providers>`__
and the appropriate :ref:`TYPO3 implementation details<t3coreapi:symfony-expression-language>`.

Register the expression language provider in the extension file
:file:`Configuration/ExpressionLanguage.php`. Make sure your expression
language provider implements :php:`Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface`.

.. code-block:: php
    :caption: EXT:some_extension/Configuration/ExpressionLanguage.php

    return [
        'form' => [
            Vendor\MyExtension\ExpressionLanguage\CustomExpressionLanguageProvider::class,
        ],
    ];

.. _concepts-variants-custom-language-variables:

Adding own expression language variables
----------------------------------------

If you need to add custom variables to the expression language you can
extend it. Then the variables are ready to be checked in conditions.

Register a custom expression language provider as written above and
provide the expression language variables:

.. code-block:: php
    :caption: EXT:some_extension/Classes/ExpressionLanguage/CustomExpressionLanguageProvider.php

    class CustomExpressionLanguageProvider extends AbstractProvider
    {
        public function __construct()
        {
            $this->expressionLanguageVariables = [
                'variableA' => 'valueB',
            ];
        }
    }
