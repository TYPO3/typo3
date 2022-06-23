.. include:: /Includes.rst.txt


.. _concepts-frontendrendering:

==================
Frontend rendering
==================


.. _concepts-frontendrendering-templates:

Templates
=========

The Fluid templates of the form framework are based on Twitter Bootstrap.


.. _concepts-frontendrendering-templates-customtemplates:

Custom templates
----------------

If you want to use custom Fluid templates for the frontend output of the
form elements, you cannot register an additional template path using
TypoScript. Instead, the registration of new template paths has to be done
via YAML. The settings are part of the ``prototypes`` configuration.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               Form:
                 renderingOptions:
                   templateRootPaths:
                     100: 'EXT:my_site_package/Resources/Private/Frontend/Templates/'
                   partialRootPaths:
                     100: 'EXT:my_site_package/Resources/Private/Frontend/Partials/'
                   layoutRootPaths:
                     100: 'EXT:my_site_package/Resources/Private/Frontend/Layouts/'

For each ``form definition`` - which references the prototype ``standard`` -
the form framework will additionally look for Fluid templates within the
path 'EXT:my_site_package/Resources/Private/Frontend/[*]' as set above.
Apart from the 'Form' element, the process will search for templates within
the ``partialRootPaths`` folder. The name of the partial is derived from the
property ``formElementTypeIdentifier``. For example, the template of the
form element ``Text`` must be stored within the ``partialRootPaths`` folder
named ``Text.html``. In contrast, the template of the ``Form`` element must
reside within the ``templateRootPaths`` folder. According to the introduced
logic, the template name must be ``Form.html``.


.. _concepts-frontendrendering-templates-singlevalues:

Access single values in finisher templates
------------------------------------------

You can access single form values and place them freely in your finisher templates.
The ``RenderFormValueViewHelper`` does the job. The viewhelper accepts a single form
element and renders it. Have a look at the following example. In order to output
the value of the field ``message`` the "RenderFormValueViewHelper"
is called with two parameters. ``{formValue.processedValue}`` contains the specific
value which can be manipulated with Fluid or styled etc.

.. code-block:: html

   <formvh:renderFormValue renderable="{page.rootForm.elements.message}" as="formValue">
       {formValue.processedValue}
   </formvh:renderFormValue>

If you don't know the names of your form elements, you could look them up in your
form definition. In addition, you can use the following snippet to lists all of
the available elements.

.. code-block:: html

   <f:debug>{page.rootForm.elements}</f:debug>


.. _concepts-frontendrendering-translation:

Translation
===========


.. _concepts-frontendrendering-translation-formdefinition:

Translate form definition
-------------------------

The translation of ``form definitions`` works differently to the translation
of the backend aspects. Currently, there is no graphical user interface
supporting the translation process.

If the backend editor needed to translate the ``form definition`` properties
in the same way the backend aspects are translated, he/ she would see long
and unwieldy translation keys while editing a form within the ``form editor``.
In order to avoid this, rather the element properties are translated than
their values. Thus, the form framework does not look for translation keys
within the translation file. Instead, the system searches for translations
of the form element properties independent of their property values. The
property values are ignored if the process finds a proper entry within the
translation file. As a result, the property values are overridden by the
translated value.

This approach is a compromise between two scenarios: the exclusive usage of
the ``form editor`` and/ or the manual creation of ``form definitions``
which can afterwards (theoretically) be edited with the ``form editor``. In
addition, the described compromise allows the editor to create forms in the
default language whose form element property values are displayed as
specified in the ``form editor``. Based on this, an integrator could provide
additional language files which automatically translate the specific form.

Additional translation files can be defined as follows:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               Form:
                 renderingOptions:
                   translation:
                     translationFiles:
                       # custom translation file
                       20: 'EXT:my_site_package/Resources/Private/Language/Form/locallang.xlf'

The array is processed from the highest key to the lowest, i.e. your
translation file with the key ``20`` is processed first. If the look-up
process does not find a key within all of the provided files, the
property value will be displayed unmodified.

The following properties can be translated:

- label
- properties.[*]
- properties.options.[*]
- properties.fluidAdditionalAttributes.[*]
- renderingOptions.[*]

The translation keys are put together based on a specific pattern. In
addition, a fallback chain that depends on the form element identifiers
exists. As a result, the following translation scenarios are possible:

- translation of a form element property for a specific form and form
  element
- translation of a form element property for a specific form element and
  various forms
- translation of a form element property for an element type and various
  forms, e.g. the ``Page`` element

The look-up process searches for translation keys in all given translation
files based on the following order:

- ``<formDefinitionIdentifier>.element.<elementIdentifier>.properties.<propertyName>``
- ``element.<formElementIdentifier>.properties.<propertyName>``
- ``element.<elementType>.properties.<propertyName>``

Form elements with option properties (``properties.options``), like the
``Select`` element, feature the following look-up process:

- ``<formDefinitionIdentifier>.element.<elementIdentifier>.properties.options.<propertyValue>``
- ``element.<elementIdentifier>.properties.options.<propertyValue>``


Example
"""""""

.. code-block:: yaml

   identifier: ApplicationForm
   type: Form
   prototypeName: standard
   label: 'Application form'

   renderables:
     -
       identifier: GeneralInformation
       type: Page
       label: 'General information'

       renderables:
         -
           identifier: LastName
           type: Text
           label: 'Last name'
           properties:
             placeholder: 'Please enter your last name.'
           defaultValue: ''
         -
           identifier: Software
           type: MultiSelect
           label: 'Known software'
           properties:
             options:
               value1: TYPO3
               value2: Neos

For the form element ``LastName``, the process will look for the following
translation keys within the translation files:

- ``ApplicationForm.element.LastName.properties.label``
- ``element.LastName.properties.label``
- ``element.Text.properties.label``

If none of the above-mentioned keys exist, 'Last name' will be displayed.

For the form element ``Software``, the process will look for the following
translation keys within the translation files:

- ``ApplicationForm.element.Software.properties.label``
- ``element.Software.properties.label``
- ``element.MultiSelect.properties.label``

If none of the above-mentioned keys exist, 'Known software' will be
displayed. The option properties are addressed as follows:

- ``ApplicationForm.element.Software.properties.options.value1``
- ``element.Software.properties.options.value1``
- ``ApplicationForm.element.Software.properties.options.value2``
- ``element.Software.properties.options.value2``

If none of the above-mentioned keys exist, 'TYPO3' will be displayed as
label for the first option and 'Neos' as label for the second option.


.. _concepts-frontendrendering-translation-validationerrors:

Translation of validation messages
----------------------------------

The translation of validation messages is similar to the translation of
``form definitions``. The same translation files can be used. If the look-up
process does not find a key within the provided files, the appropriate
message of the Extbase framework will be displayed. EXT:form already
translates all of those validators by default.

As mentioned above, the translation keys are put together based on a
specific pattern. Furthermore, the fallback chain exists here as well. Thus,
the following translation scenarios are possible:

- translation of validation messages for a specific validator of a concrete
  form element and concrete form
- translation of validation messages for a specific validator of various
  form elements within a concrete form
- translation of validation messages for a specific validator of a concrete
  form element in various forms
- translation of validation messages for a specific validator within various
  forms

In Extbase, the validation messages are identified with the help of
numerical codes (UNIX timestamps). For the same validator, different codes
are valid. Read more about :ref:`concrete validator configurations <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`.

The look-up process searches for translation keys in all given translation
files based on the following order:

- ``<formDefinitionIdentifier>.validation.error.<elementIdentifier>.<validationErrorCode>``
- ``<formDefinitionIdentifier>.validation.error.<validationErrorCode>``
- ``validation.error.<elementIdentifier>.<validationErrorCode>``
- ``validation.error.<validationErrorCode>``


Example
"""""""

.. code-block:: yaml

   identifier: ContactForm
   type: Form
   prototypeName: standard
   label: 'Contact us'

   renderables:
     -
       identifier: Page1
       type: Page
       label: 'Page 1'

       renderables:
         -
           identifier: LastName
           type: Text
           label: 'Last name'
           properties:
             fluidAdditionalAttributes:
               required: required
           validators:
             -
               identifier: NotEmpty

Amongst others, the ``NotEmpty`` validator sends 1221560910 as ``<validationErrorCode>``.
If a user submits this form without providing a value for the field "Last
name", the ``NotEmpty`` validator fails. Now, the look-up process searches
for the following translation keys for the ``NotEmpty`` validator combined
with the form element ``LastName``:

- ContactForm.validation.error.LastName.1221560910
- ContactForm.validation.error.1221560910
- validation.error.LastName.1221560910
- validation.error.1221560910

As mentioned above, if there is no corresponding translation key available,
the default message of the Extbase framework will be shown.


.. _concepts-frontendrendering-translation-finishers:

Translation of finisher options
-------------------------------

The translation of finisher options is similar to the translation of
``form definitions``. The same translation files can be used. If the look-up
process does not find a key within all provided files, the property value
will be displayed unmodified.

As mentioned above, the translation keys are put together based on a
specific pattern. Furthermore, the fallback chain exists here as well. Thus,
the following translation scenarios are possible:

- translation of finisher options for a specific finisher of a concrete form
- translation of finisher options for a specific finisher of various forms

The look-up process searches for translation keys in all given translation
files based on the following order:

- ``<formDefinitionIdentifier>.finisher.<finisherIdentifier>.<optionName>``
- ``finisher.<finisherIdentifier>.<optionName>``


Example
"""""""

.. code-block:: yaml

   identifier: ContactForm
   type: Form
   prototypeName: standard
   label: 'Contact us'

   finishers:
     -
       identifier: Confirmation
       options:
         message: 'Thank you for your inquiry.'

   renderables:
     ...

The look-up process searches for the following translation keys for the
``<finisherIdentifier>`` 'Confirmation' and the option 'message':

- ``ContactForm.finisher.Confirmation.message``
- ``finisher.Confirmation.message``

If no translation key exists, the message 'Thank you for your inquiry.' will
be shown.


.. _concepts-frontendrendering-translation-arguments:

Form element translation arguments
==================================

Form element property translations and finisher option translations can use
placeholders to output translation arguments. Translations can be enriched
with variable values by passing arguments to form element properties. The
feature was introduced with :issue:`81363`.


Form element properties
-----------------------

Pure YAML is sufficient to add simple, static values:

.. code-block:: yaml

   renderables:
     - identifier: field-with-translation-arguments
       type: Checkbox
       label: This is a %s feature
       renderingOptions:
         translation:
           translationFiles:
             10: path/to/locallang.xlf
           arguments:
             label:
               - useful

This will produce the label: `This is a useful feature`.

Alternatively, translation arguments can be set via
:typoscript:`formDefinitionOverrides` in TypoScript. A common usecase is a checkbox for
user confirmation linking to details of the topic. Here it makes sense to use
YAML hashes instead of YAML lists to give sections named keys. This simplifies
references in TypoScript a lot since named keys are way more readable and also
keep the setup working in case elements are reordered. With lists and numeric
keys the TypoScript setup would also need to be updated in this case.

In the following example the list of :yaml:`renderables` has been replaced with
a hash of :yaml:`renderables` and the field :yaml:`field-with-translation-arguments`
has received a named key :yaml:`fieldWithTranslationArguments`. This key can be anything
as long as it is unique on the same level, usually simply copying the :yaml:`identifier`
should be enough:

.. code-block:: yaml

   renderables:
     fieldWithTranslationArguments:
       identifier: field-with-translation-arguments
       type: Checkbox
       label: I agree to the <a href="%s">terms and conditions</a>
       renderingOptions:
         translation:
           translationFiles:
             10: path/to/locallang.xlf

In case the label defines HTML markup - like in the above example, it must
be wrapped into `CDATA` tags in the corresponding `path/to/locallang.xlf` file,
to prevent analysing of the character data by the parser. Additionally, the
corresponding label should be rendered using the :html:`<f:format.raw>`
view helper in fluid templates, to prevent escaping of the HTML tags.

.. code-block:: xml

    <trans-unit id="<form-id>.element.field-with-translation-arguments.properties.label">
        <source><![CDATA[I agree to the <a href="%s">terms and conditions</a>]]></source>
    </trans-unit>

The following TypoScript setup uses the named key :yaml:`fieldWithTranslationArguments` to refer
to the field and adds a page URL as translation argument:

.. code-block:: typoscript

   plugin.tx_form {
      settings {
         formDefinitionOverrides {
            <form-id> {
               renderables {
                  0 {
                     # Page
                     renderables {
                        fieldWithTranslationArguments {
                           renderingOptions {
                              translation {
                                 arguments {
                                    label {
                                       0 = TEXT
                                       0.typolink {
                                          # Terms and conditions page, could be
                                          # set also via TypoScript constants
                                          parameter = 42
                                          returnLast = url
                                       }
                                    }
                                 }
                              }
                           }
                        }
                     }
                  }
               }
            }
         }
      }
   }

The :yaml:`Page` element of the form definition was not registered with a named key so a numeric
key :yaml:`0` must be used which, as mentioned above, is prone to errors when more pages are added
or pages are reordered.

.. important::

   There must be at least one translation file with a translation for the
   configured form element property. Arguments are not inserted into default
   values defined in a form definition.


Finishers
---------

The same mechanism (YAML, YAML + TypoScript) works for finisher options:

.. code-block:: yaml

   finishers:
     finisherWithTranslationArguments:
       identifier: EmailToReceiver
       options:
         subject: My %s subject
         recipients:
           your.company@example.com: 'Your Company name'
           ceo@example.com: 'CEO'
         senderAddress: bar@example.org
         translation:
           translationFiles:
             10: path/to/locallang.xlf
           arguments:
             subject:
               - awesome

This will produce `My awesome subject`.


.. _concepts-frontendrendering-basiccodecomponents:

Basic code components
=====================

.. figure:: ../../Images/basic_code_components.png
   :alt: Basic code components

   Basic code components


.. _concepts-frontendrendering-basiccodecomponents-formdefinition:

TYPO3\\CMS\\Form\\Domain\\Model\\FormDefinition
-----------------------------------------------

The class ``TYPO3\CMS\Form\Domain\Model\FormDefinition`` encapsulates
a complete ``form definition``, with all of its

- pages,
- form elements,
- applicable validation rules, and
- finishers, which should be executed when the form is submitted.

The FormDefinition domain model is not modified when the form is executed.


.. _concepts-frontendrendering-basiccodecomponents-formdefinition-anatomy:

The anatomy of a form
"""""""""""""""""""""

A ``FormDefinition`` domain model consists of multiple ``Page`` objects.
When a form is displayed, only one ``Page`` is visible at any given time.
Moreover, there is a navigation to go back and forth between those pages. A
``Page`` consists of multiple ``FormElements`` which represent the input
fields, textareas, checkboxes, etc. shown on a page. The ``FormDefinition``
domain model, ``Page`` and ``FormElement`` objects have ``identifier``
properties which must be unique for each given ``<formElementTypeIdentifier>``,
i.e. the ``FormDefinition`` domain model and a ``FormElement`` object may
have the same ``identifier`` but having the same identifier for two
``FormElement`` objects is disallowed.


.. _concepts-frontendrendering-basiccodecomponents-formdefinition-anatomy-example:

Example
'''''''

Basically, you can manually create a ``FormDefinition`` domain model just
by calling the API methods on it, or you can use a ``FormFactory`` to build
the form from a different representation format such as YAML::

   $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'myForm');

   $page1 = GeneralUtility::makeInstance(Page::class, 'page1');
   $formDefinition->addPage($page);

   // second argument is the <formElementTypeIdentifier> of the form element
   $element1 = GeneralUtility::makeInstance(GenericFormElement::class, 'title', 'Text');
   $page1->addElement($element1);


.. _concepts-frontendrendering-basiccodecomponents-formdefinition-createformusingabstracttypes:

Creating a form using abstract form element types
"""""""""""""""""""""""""""""""""""""""""""""""""

While you can use the ``TYPO3\CMS\Form\Domain\Model\FormDefinition::addPage()``
or ``TYPO3\CMS\Form\Domain\Model\FormElements\Page::addElement()`` methods
and create the ``Page`` and ``FormElement`` objects manually, it is often
better to use the corresponding create* methods (``TYPO3\CMS\Form\Domain\Model\FormDefinition::createPage()``
and ``TYPO3\CMS\Form\Domain\Model\FormElements\Page::createElement()``), as
you pass them an abstract ``<formElementTypeIdentifier>`` such as ``Text``
or ``Page``. EXT:form will automatically resolve the implementation class
name and set default values.

The :ref:`simple example <concepts-frontendrendering-basiccodecomponents-formdefinition-anatomy-example>`
shown above should be rewritten as follows::

   // we will come back to this later on
   $prototypeConfiguration = [];

   $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'myForm', $prototypeConfiguration);
   $page1 = $formDefinition->createPage('page1');
   $element1 = $page1->addElement('title', 'Text');

You might wonder how the system knows that the element ``Text`` is
implemented by using a ``GenericFormElement``. This is configured in the
``$prototypeConfiguration``. To make the example from above actually work,
we need to add some meaningful values to ``$prototypeConfiguration``::

   $prototypeConfiguration = [
       'formElementsDefinition' => [
           'Page' => [
               'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\Page'
           ],
           'Text' => [
               'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement'
           ],
       ],
   ];

For each abstract ``<formElementTypeIdentifier>`` we have to add some
configuration. In the snippet above, we only define the ``implementation
class name``. Apart from that, it is always possible to set default values
for all configuration options of such elements, as the following example
shows::

   $prototypeConfiguration = [
       'formElementsDefinition' => [
           'Page' => [
               'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\Page',
               'label' => 'This is the label of the page if nothing else is specified'
           ],
           'Text' => [
               'implementationClassName' => 'TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement',
               'label' = >'Default Label',
               'defaultValue' => 'Default form element value',
               'properties' => [
                   'placeholder' => 'Text that is shown if element is empty'
               ],
           ],
       ],
   ];


.. _concepts-frontendrendering-basiccodecomponents-formdefinition-preconfiguredconfiguration:

Using pre-configured $prototypeConfiguration
""""""""""""""""""""""""""""""""""""""""""""

Often, it does not make sense to manually create the $prototypeConfiguration
array. Bigger parts of this array are pre-configured in the extensions's
YAML settings. The ``TYPO3\CMS\Form\Domain\Configuration\ConfigurationService``
contains helper methods which return the ready-to-use ``$prototypeConfiguration``.


.. _concepts-frontendrendering-basiccodecomponents-formdefinition-rednering:

Rendering a FormDefinition
""""""""""""""""""""""""""

To trigger the rendering of a ``FormDefinition`` domain model, the current
``TYPO3\CMS\Extbase\Mvc\Web\Request`` needs to be bound to the
``FormDefinition``. This binding results in a ``TYPO3\CMS\Form\Domain\Runtime\FormRuntime``
object which contains the ``Runtime State`` of the form. Among other things,
this object includes the currently inserted values::

   // $currentRequest needs to be available.
   // Inside a controller, you would use $this->request
   $form = $formDefinition->bind($currentRequest);
   // now, you can use the $form object to get information about the currently entered values, etc.


.. _concepts-frontendrendering-basiccodecomponents-formruntime:

TYPO3\\CMS\\Form\\Domain\\Runtime\\FormRuntime
----------------------------------------------

This class implements the runtime logic of a form, i.e. the class

- decides which page is currently shown,
- determines the current values of the form
- triggers validation and property mappings.

You generally receive an instance of this class by calling ``TYPO3\CMS\Form\Domain\Model\FormDefinition::bind()``.


.. _concepts-frontendrendering-basiccodecomponents-formruntime-render:

Rendering a form
""""""""""""""""

Rendering a form is easy. Just call ``render()`` on the ``FormRuntime``::

   $form = $formDefinition->bind($request);
   $renderedForm = $form->render();


.. _concepts-frontendrendering-basiccodecomponents-formruntime-accessingformvalues:

Accessing form values
"""""""""""""""""""""

In order to get the values the user has entered into the form, you can
access the ``FormRuntime`` object like an array. If a form element with the
identifier ``firstName`` exists, you can use ``$form['firstName']`` to
retrieve its current value. You can set values the same way.


.. _concepts-frontendrendering-basiccodecomponents-formruntime-renderinginternals:

Rendering internals
"""""""""""""""""""

The ``FormRuntime`` inquires the ``FormDefinition`` domain model regarding
the configured renderer (``TYPO3\CMS\Form\Domain\Model\FormDefinition::getRendererClassName()``)
and then triggers render() on this Renderer.

This allows you to declaratively define how a form should be rendered.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               Form:
                 rendererClassName: 'TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer'


.. _concepts-frontendrendering-basiccodecomponents-fluidformrenderer:

TYPO3\\CMS\\Form\\Domain\\Renderer\\FluidFormRenderer
-----------------------------------------------------

This class is a  ``TYPO3\CMS\Form\Domain\Renderer\RendererInterface``
implementation which used to render a ``FormDefinition`` domain model. It
is the default EXT:form renderer.

Learn more about the :ref:`FluidFormRenderer Options<apireference-frontendrendering-fluidformrenderer-options>`.


.. _concepts-frontendrendering-codecomponents-customformelementimplementations:

Custom form element implementations
-----------------------------------

EXT:form ships a decent amount of hooks which are available at crucial
points of the life cycle of a ``FormElement``. Most of the time, own
implementations are therefore unnecessary. An own form element can be
defined by:

- writing some configuration, and
- utilizing the standard implementation of ``TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement``.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               CustomFormElementIdentifier:
                 implementationClassName: 'TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement'

With the provided hooks, this ``FormElement`` can now be manipulated.

If you insist on your own implementation, the abstract class ``TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement``
offers a perfect entry point. In addition, we recommend checking-out ``TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable``.
All of your own form element implementations must be programmed to the
interface ``TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface``.
It is a good idea to derive your implementation from ``TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement``.


.. _concepts-frontendrendering-renderviewHelper:

"render" viewHelper
===================

The ``RenderViewHelper`` is the actual starting point for form rendering and
not the typical Extbase Controller as you may know it.

For more technical insights read more about the viewHelper's :ref:`arguments<apireference-frontendrendering-renderviewHelper-arguments>`.


.. _concepts-frontendrendering-fluidtemplate:

Render through FLUIDTEMPLATE (without controller)
-------------------------------------------------

.. code-block:: typoscript

   tt_content.custom_content_element = COA_INT
   tt_content.custom_content_element {
       10 = < lib.stdheader
       20 = FLUIDTEMPLATE
       20 {
           file = EXT:my_site_package/Resources/Private/Templates/CustomContentElement.html
           settings {
               persistenceIdentifier = EXT:my_site_package/Resources/Private/Forms/MyForm.yaml
           }
           extbase.pluginName = Formframework
           extbase.controllerExtensionName = Form
           extbase.controllerName = FormFrontend
           extbase.controllerActionName = perform
       }
   }

``my_site_package/Resources/Private/Templates/CustomContentElement.html``:

.. code-block:: html

   <formvh:render persistenceIdentifier="{settings.persistenceIdentifier}" />


.. _concepts-frontendrendering-extbase:

Render within your own Extbase extension
----------------------------------------

It is straight forward. Use the ``RenderViewHelper`` like this and you are
done:

.. code-block:: html

   <formvh:render persistenceIdentifier="EXT:my_site_package/Resources/Private/Forms/MyForm.yaml"/>

Point the property ``controllerAction`` to the desired action name and
provide values for the other parameters displayed below (you might need
those).

.. code-block:: yaml

   type: Form
   identifier: 'example-form'
   label: 'TYPO3 is cool'
   prototypeName: standard
   renderingOptions:
     controllerAction: perform
     addQueryString: false
     argumentsToBeExcludedFromQueryString: []
     additionalParams: []

   renderables:
     ...


.. note::

   In general, you can override each and every ``form definition`` with the help
   of TypoScript (see ':ref:`TypoScript overrides<concepts-frontendrendering-runtimemanipulation-typoscriptoverrides>`').
   This feature is not supported when you are rendering forms via the ``RenderViewHelper``.

   Luckily, there is a solution for your problem: use the ':ref:`overrideConfiguration<apireference-frontendrendering-renderviewHelper-overrideconfiguration>`'
   parameter instead. This way, you can override the form definition within your template.
   Provide an according array as shown in the example below.

   .. code-block:: html

      <formvh:render persistenceIdentifier="EXT:my_site_package/Resources/Private/Forms/MyForm.yaml" overrideConfiguration="{renderables: {0: {renderables: {0: {label: 'My shiny new label'}}}}}"/>


.. _concepts-frontendrendering-programmatically:

Build forms programmatically
============================

To learn more about this topic, head to the chapter ':ref:`Build forms programmatically<apireference-frontendrendering-programmatically>`'
which is part of the API reference section.


.. _concepts-frontendrendering-runtimemanipulation:

Runtime manipulation
====================


.. _concepts-frontendrendering-runtimemanipulation-hooks:

Hooks
-----

EXT:form implements a decent amount of hooks that allow the manipulation of
your forms during runtime. In this way, it is possible to, for example,

- ... prefill form elements with values from your database,
- ... skip a whole page based on the value of a certain form element,
- ... mark a form element as mandatory depending of the chosen value of another
  form element.

Please check out the ':ref:`API reference section<apireference-frontendrendering-runtimemanipulation-hooks>`'
for more details.


.. _concepts-frontendrendering-runtimemanipulation-typoscriptoverrides:

TypoScript overrides
--------------------

Each and every ``form definition`` can be overridden via TypoScript if the
``FormFrontendController`` of EXT:form is used to render the form. Normally,
this is the case if the form has been added to the page using the form
plugin or when rendering the form via :ref:`FLUIDTEMPLATE <concepts-frontendrendering-fluidtemplate>`.

The overriding of settings with TypoScript's help takes place after the :ref:`custom finisher settings<concepts-formplugin>`
of the form plugin have been loaded. In this way, you are able to manipulate
the ``form definition`` for a single page. In doing so, the altered
``form definition`` is passed to the ``RenderViewHelper`` which then
generates the form programmatically. At this point, you can still change the
form elements using the above-mentioned concept of :ref:`hooks<concepts-frontendrendering-runtimemanipulation-hooks>`.

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           formDefinitionOverrides {
               <formDefinitionIdentifier> {
                   renderables {
                       0 {
                           renderables {
                               0 {
                                   label = TEXT
                                   label.value = Overridden label
                               }
                           }
                       }
                   }
               }
           }
       }
   }
