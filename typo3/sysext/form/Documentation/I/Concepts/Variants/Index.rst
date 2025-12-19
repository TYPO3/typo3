.. include:: /Includes.rst.txt


.. _concepts-variants:

Variants
========


.. _concepts-variants-basics:

Basics
------

A variant is an "alternative" form definition section that allows you to change
properties of form elements, validators, and finishers. Variants are activated
by conditions. This allows you to:

* translate form element values depending on the frontend language
* set and remove validators from one form element depending on the
  value of another form element
* hide entire steps (form pages) depending on the value of a form
  element
* set finisher options depending on the value of a form element
* hide a form element in particular finishers and on the summary step

Form element variants can be defined statically in
form definitions or created programmatically through an API. The
variants defined in a form definition are applied to
a form based on their conditions at runtime. Programmatically defined variants
can be applied at any time.

Variant conditions can be evaluated programmatically
at any time. However, some conditions are only available at runtime,
for example, checking a form element value.

Custom conditions and operators can be easily added.

Only the form element properties listed in a variant are applied to the
form element, all other properties are retained. An exception to this
rule are finishers and validators. If finishers or validators are
**not** defined in a variant, the original finishers and validators
will be used. If at least one finisher or validator is defined in a
variant, the original finishers and validators are overwritten
by the finishers and validators in the variant.

Variants defined in a form definition are **all** processed and
applied in the order of their matching conditions. This means if
variant 1 sets the label of a form element to "X" and variant 2 sets
the label to "Y", then variant 2 is applied, i.e. the label will be "Y".

.. note::
   Currently it is **not** possible to define variants in
   the backend form editor.


.. _concepts-variants-enabled-property:

Rendering option ``enabled``
----------------------------

The rendering option :yaml:`enabled` is available for all finishers and
form elements except the root form element and the first form
page. The option accepts a boolean value (:yaml:`true` or :yaml:`false`).

Setting a form element to :yaml:`enabled: true` renders it in the
frontend and enables processing of its values, including property mapping
and validation. Setting :yaml:`enabled: false` disables it in the frontend. All
form elements and finishers except the root form element and the first form page can be enabled
or disabled.

Setting a finisher to :yaml:`enabled: true` executes it when
the form is submitted. Setting :yaml:`enabled: false` skips the finisher.

By default, :yaml:`enabled` is set to :yaml:`true`.

See :ref:`examples<concepts-variants-examples-hide-form-elements>`
below to learn more.


.. _concepts-variants-definition:

Definition of variants
----------------------

Variants are defined at the form element level in YAML. Here is an example of a text
form element variant:

.. code-block:: yaml

   type: Text
   identifier: text-1
   label: Foo
   variants:
     -
       identifier: variant-1
       condition: 'traverse(formValues, "checkbox-1") == 1'
       # If the condition matches, the label property of the form
       # element is set to the value 'Bar'
       label: Bar


The :yaml:`identifier` must be unique at the form element level.

Each variant has a single :yaml:`condition` which applies the variant if the
condition is satisfied. The
properties in the variant are applied to the form element. In the
example above the label of :yaml:`text-1` is
changed to ``Bar`` if the checkbox :yaml:`checkbox-1` is checked.

The following properties can be overwritten by :yaml:`Form` (the topmost element)
variants:

* :yaml:`label`
* :yaml:`renderingOptions`
* :yaml:`finishers`
* :yaml:`rendererClassName`

The following properties can be overwritten by all other form element variants:

* :yaml:`enabled`
* :yaml:`label`
* :yaml:`defaultValue`
* :yaml:`properties`
* :yaml:`renderingOptions`
* :yaml:`validators`

.. note::
   Unset individual list items in select option variants by marking the values with
   :code:`__UNSET`. See :ref:`example <concepts-variants-examples-remove-options>` below.

.. _concepts-variants-conditions:

Conditions
----------

The form framework uses the Symfony component `expression language <https://symfony.com/doc/4.1/components/expression_language.html>`_
for conditions. An expression is a one-liner that returns a boolean value, for example,
:yaml:`applicationContext matches "#Production/Local#"`. For further information see
the `Symfony docs <https://symfony.com/doc/4.1/components/expression_language/syntax.html>`_.
The form framework extends the expression language with variables to access
form values and environment settings.

.. _concepts-variants-conditions-formruntime:

``formRuntime`` (object)
^^^^^^^^^^^^^^^^^^^^^^^^

You can access every public method of :php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime`.
Learn more :ref:`here<apireference-frontendrendering-programmatically-apimethods-formruntime>`.

For example:

:yaml:`formRuntime.getIdentifier() == "test"`.

.. _concepts-variants-conditions-renderable:

``renderable`` (VariableRenderableInterface)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`renderable` contains the instance of renderable that the condition
is applied to. This can be used e.g. to access the identifier of the
current renderable without having to duplicate it.

For example:

:yaml:`traverse(formValues, renderable.getIdentifier()) == "special value"`.

.. _concepts-variants-conditions-formvalues:

``formValues`` (array)
^^^^^^^^^^^^^^^^^^^^^^

:yaml:`formValues` holds all the submitted form element values. Each
key in the array represents a form element identifier.

For example:

:yaml:`traverse(formValues, "text-1") == "yes"`.

.. _concepts-variants-conditions-stepidentifier:

``stepIdentifier`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`stepIdentifier` is set to the :yaml:`identifier` of the current
step.

For example:

:yaml:`stepIdentifier == "page-1"`.

.. _concepts-variants-conditions-steptype:

``stepType`` (string)
^^^^^^^^^^^^^^^^^^^^^

:yaml:`stepType` is set to the :yaml:`type` of the current step.

For example:

:yaml:`stepType == "SummaryPage"`.

.. _concepts-variants-conditions-finisheridentifer:

``finisherIdentifier`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`finisherIdentifier` is set to the :yaml:`identifier` of the
current finisher or an empty string (if no finishers are executed).

For example:

:yaml:`finisherIdentifier == "EmailToSender"`.

.. _concepts-variants-conditions-site:

``site`` (object)
^^^^^^^^^^^^^^^^^

You can access every public method in :php:`\TYPO3\CMS\Core\Site\Entity\Site`.
The following are the most important ones:

* getSettings() / The site settings array
* getDefaultLanguage() / The default language object for the current site
* getConfiguration() / The whole configuration of the current site
* getIdentifier() / The identifier of the current site
* getBase() / The base URL of the current site
* getRootPageId() / The ID of the root page of the current site
* getLanguages() / An array of available languages for the current site
* getSets() / Configured site sets of a site (new in TYPO3 v13+)

For example:

:yaml:`site("settings").get("myVariable") == "something"`.
:yaml:`site("rootPageId") == "42"`.

More details on the `Site` object can be found in
:ref:`Using site configuration in conditions <t3coreapi:sitehandling-inConditions>`.

.. _concepts-variants-conditions-sitelanguage:

``siteLanguage`` (object)
^^^^^^^^^^^^^^^^^^^^^^^^^

You can access every public method in :php:`\TYPO3\CMS\Core\Site\Entity\SiteLanguage`.
The most important ones are:

* getLanguageId() / The sys_language_uid.
* getLocale() / The language locale, for example 'en_US.UTF-8'.
* getTypo3Language() / The language key for XLF files, for example, 'de' or 'default'.
* getTwoLetterIsoCode() / Returns the ISO-639-1 language ISO code, for example, 'de'.

For example:

:yaml:`siteLanguage("locale").getName() == "de-DE"`.

.. _concepts-variants-conditions-applicationcontext:

``applicationContext`` (string)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`applicationContext` is set to the application context
(@see GeneralUtility::getApplicationContext()).

For example:

:yaml:`applicationContext matches "#Production/Local#"`.

.. _concepts-variants-conditions-contentobject:

``contentObject`` (array)
^^^^^^^^^^^^^^^^^^^^^^^^^

:yaml:`contentObject` contains the data of the current content object
or an empty array if no content object is available.

For example:

:yaml:`contentObject["pid"] in [23, 42]`.


.. _concepts-variants-programmatically:

Working with variants programmatically
--------------------------------------

Create a variant with conditions through the PHP API::

   /** @var TYPO3\CMS\Form\Domain\Model\Renderable\RenderableVariantInterface $variant */
   $variant = $formElement->createVariant([
       'identifier' => 'variant-1',
       'condition' => 'traverse(formValues, "checkbox-1") == 1',
       'label' => 'foo',
   ]);


Get all the variants of a form element::

   /** @var TYPO3\CMS\Form\Domain\Model\Renderable\RenderableVariantInterface[] $variants */
   $variants = $formElement->getVariants();


Apply a variant to a form element regardless of its conditions::

   $formElement->applyVariant($variant);


.. _concepts-variants-examples:

Examples
--------

Here are some more complex examples to show you what is possible with the
form framework.


.. _concepts-variants-examples-translation:

Translation of form elements
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In this example, form, page and text elements have variants so that they are translated differently depending on
the frontend language (whether it is German or English).

.. code-block:: yaml
   :emphasize-lines: 9,10,24,25,40,41

   type: Form
   prototypeName: standard
   identifier: contact-form
   label: Kontaktformular
   renderingOptions:
     submitButtonLabel: Senden
   variants:
     -
       identifier: language-variant-1
       condition: 'siteLanguage("locale").getName() == "en-US"'
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
           condition: 'siteLanguage("locale").getName() == "en-US"'
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
               condition: 'siteLanguage("locale").getName() == "en-US"'
               label: Full name
               properties:
                 fluidAdditionalAttributes:
                   placeholder: Your full name


.. _concepts-variants-examples-validation:

Adding validators dynamically
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In this example, the :yaml:`email-address` field has a variant that adds validators
if :yaml:`checkbox-1` is checked.


.. code-block:: yaml
   :emphasize-lines: 18,19

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
               condition: 'traverse(formValues, "checkbox-1") == 1'
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

In this example, the form element :yaml:`email-address` has
been enabled explicitly but this can be left out as this is
the default state. The form element :yaml:`text-3` has been disabled
to (temporarily) remove it from the form. The
field :yaml:`text-1` has a variant that hides it in all finishers and on the summary step.
The :yaml:`EmailToSender` finisher contains form values (:yaml:`email-address`
and :yaml:`name`). The :yaml:`EmailToSender` finisher is only enabled if
:yaml:`checkbox-1` has been checked by the user, otherwise it is skipped.

.. code-block:: yaml
   :emphasize-lines: 15,19,23,32,36,39,42,51

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

In this example, the second step (:yaml:`page-2`) has a variant that disables it
if :yaml:`checkbox-1` is checked. :yaml:`checkbox-1` has a variant which
disables it on the summary step.

.. code-block:: yaml
   :emphasize-lines: 17, 21,22,24,27,31,32,34

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
           condition: 'traverse(formValues, "checkbox-1") == 1'
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

In this example, the form has a variant so that the finisher has different values
depending on the application context.

.. code-block:: yaml
   :emphasize-lines: 9,12,13,18

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

In this example, a select form element has a variant which removes an option for
a specific locale.

.. code-block:: yaml
   :emphasize-lines: 13,24,25,28

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
               condition: 'siteLanguage("locale").getName() == "zh-CN"'
               properties:
                 options:
                   miss: __UNSET


.. _concepts-variants-custom-language-providers:

Adding your own expression language providers
---------------------------------------------

You can extend the expression language with your own custom functions. For more
information see the official `docs <https://symfony.com/doc/5.4/components/expression_language/extending.html#using-expression-providers>`__
and the appropriate :ref:`TYPO3 implementation details<t3coreapi:symfony-expression-language>`.

Register your own expression language provider class in
:file:`Configuration/ExpressionLanguage.php` and create it, making sure it
implements :php:`Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface`.

.. code-block:: php
    :caption: EXT:some_extension/Configuration/ExpressionLanguage.php

    return [
        'form' => [
            Vendor\MyExtension\ExpressionLanguage\CustomExpressionLanguageProvider::class,
        ],
    ];

.. _concepts-variants-custom-language-variables:

Adding your own expression language variables
---------------------------------------------

You can extend the expression language with your own variables. These
variables can be used in conditions.

Register your own expression language provider class in
:file:`Configuration/ExpressionLanguage.php` as above and
and create it as follows:

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
