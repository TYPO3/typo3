..  include:: /Includes.rst.txt
..  _concepts-finishers-customfinisherimplementations:

===============
Custom finisher
===============

..  include:: /Includes/_NoteFinisher.rst

..  contents:: Table of contents
    :local:

..  _concepts-finishers-custom-howtowrite:

Write a custom finisher
=======================

To make your finisher configurable by users in the backend form editor, see
:ref:`here <concepts-finishers-customfinisherimplementations-extend-gui>`.

Add a new finisher to the form configuration prototype by defining a
`finishersDefinition`. Set the `implementationClassName` property to your new implementation class.

..  literalinclude:: _codesnippets/_finishersDefinition.yaml
    :caption: EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml

`Register <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-custom-extend-gui-configuration>`_
your custom form definition.

Add options to your finisher with the `options` property. Options
are default values which can be overridden in the `form definition`.

..  _concepts-finishers-custom-default-value:

Define default values
---------------------

..  literalinclude:: _codesnippets/_CustomFinisher.yaml
    :caption: EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml

..  _concepts-finishers-custom-option-override:

Override options using the `form definition`
--------------------------------------------

..  literalinclude:: _codesnippets/_my_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

A finisher must implement :php-short:`TYPO3\CMS\Form\Domain\Finishers\FinisherInterface`
and should extend :php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`.
In doing so, in the logic of the
finisher the method `executeInternal()` will be called first.

..  _concepts-finishers-customfinisherimplementations-accessingoptions:

Accessing finisher options
==========================

If your finisher class extends :php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`,
you can access the option values in the finisher using method `parseOption()`:

..  code-block:: php

    $yourCustomOption = $this->parseOption('yourCustomOption');

`parseOption()` looks for 'yourCustomOption' in your
`form definition`.

..  literalinclude:: _codesnippets/_CustomFinisher.yaml
    :caption: EXT:my_site_package/Classes/Domain/Finishers/CustomFinisher.yaml

If it can't find it, `parseOption()` checks

1.  for a default value in the `prototype` configuration,

2.  for `$defaultOptions` inside your finisher class:



If it doesn't find anything, `parseOption()` returns `null`.

If it finds the option, the process checks whether the option value will
access :ref:`FormRuntime values <concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`.
If the `FormRuntime` returns a positive result, it is checked whether the
option value :ref:`can access values of preceding finishers <concepts-finishers-customfinisherimplementations-finishercontext-sharedatabetweenfinishers>`.
At the end, it :ref:`translates the finisher options <concepts-frontendrendering-translation-finishers>`.

..  _concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor:

Accessing form runtime values
=============================

You can populate finisher options with
submitted form values using the `parseOption()` method.
You can access values of the `FormRuntime` and therefore values in every
form element by encapsulating option values with `{}`. Below, if there is a
form element with the `identifier` 'subject', you can access the value
in the finisher configuration:

..  literalinclude:: _codesnippets/_my_form_extended.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  code-block:: php

    // $yourCustomOption contains the value of the form element with the
    // identifier 'subject'
    $yourCustomOption = $this->parseOption('yourCustomOption');

You can use `{__currentTimestamp}` as an option value to return the
current UNIX timestamp.

..  _concepts-finishers-customfinisherimplementations-accessingoptions-displayvalue:

Display values and submitted values
-----------------------------------

..  versionchanged:: 14.3.7
    :changelog: important-106903-1787754756

    Before, the built-in finishers used the submitted value, for example the
    option key, in all options.

`parseOption()` replaces `{<elementIdentifier>}` with the submitted value of
the form element. For a select, radio button, multi checkbox, or country
select element, this value is the option key, for example `mr`.

`parseOptionAsDisplayValue()` replaces the placeholder with the display value
instead. The form element provides this value, for example the translated
option label `Mister`. Use it for options that are read by a human. Never use
it for options that end up in a query, a stored record, or a URL.

..  code-block:: php

    // "Request from Mister" instead of "Request from mr"
    $subject = $this->parseOptionAsDisplayValue('subject');

The built-in finishers use display values for the following options:

*   `EmailFinisher`: `subject`, `title`, `message`, and `senderName`
*   `ConfirmationFinisher`: `message`
*   `FlashMessageFinisher`: `messageBody` and `messageTitle`

All other options of the built-in finishers keep the submitted value. This
includes the email addresses, the redirect targets, and all options of the
`SaveToDatabaseFinisher`.

Both methods join an array value inside a longer string with `, `, for example
the values of a multi select element in `Request for {interests}`.

..  _concepts-finishers-customfinisherimplementations-fileuploads:

Accessing uploaded files
========================

..  versionadded:: 14.2
    :changelog: feature-105708-1739721600

    Before, `FileUpload` and `ImageUpload` elements accepted only one file.

The value of a `FileUpload` or `ImageUpload` element depends on its
`multiple` property:

*   Single upload: a :php-short:`\TYPO3\CMS\Extbase\Domain\Model\FileReference`
*   Multiple upload: an
    :php-short:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` of
    :php-short:`\TYPO3\CMS\Extbase\Domain\Model\FileReference` objects

Handle both cases in a custom finisher that processes uploaded files:

..  code-block:: php
    :caption: EXT:my_site_package/Classes/Domain/Finishers/CustomFinisher.php

    use TYPO3\CMS\Core\Resource\FileInterface;
    use TYPO3\CMS\Extbase\Domain\Model\FileReference;
    use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
    use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
    use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;

    class CustomFinisher extends AbstractFinisher
    {
      protected function executeInternal(): void
      {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $elements = $formRuntime->getFormDefinition()
          ->getRenderablesRecursively();
        foreach ($elements as $element) {
          if (!$element instanceof FileUpload) {
            continue;
          }
          $value = $formRuntime[$element->getIdentifier()];
          if ($value instanceof FileReference) {
            $this->processFile($value->getOriginalResource());
          }
          if ($value instanceof ObjectStorage) {
            foreach ($value as $fileReference) {
              if ($fileReference instanceof FileReference) {
                $this->processFile(
                  $fileReference->getOriginalResource()
                );
              }
            }
          }
        }
      }

      private function processFile(FileInterface $file): void
      {
        // Your custom logic, for example move, copy, or attach the file
      }
    }

..  _concepts-finishers-customfinisherimplementations-finishercontext:

Finisher Context
================

The :php-short:`TYPO3\CMS\Form\Domain\Finishers\FinisherContext` class takes care of
transferring a finisher context to each finisher. If your finisher class extends
:php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher` the
finisher context will be available via:

..  code-block:: php

    $this->finisherContext

The  `cancel` method prevents the execution of successive finishers:

..  code-block:: php

    $this->finisherContext->cancel();

The method `getFormValues` returns the submitted form values.

..  code-block:: php

    $this->finisherContext->getFormValues();

The method `getFormRuntime` returns the `FormRuntime`:

..  code-block:: php

    $this->finisherContext->getFormRuntime();

..  _concepts-finishers-customfinisherimplementations-finishercontext-sharedatabetweenfinishers:

Share data between finishers
============================

The method `getFinisherVariableProvider` returns an
object (:php-short:`TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider`) which allows you
to store data and transfer it to other finishers. The data
can be easily accessed programmatically or inside your configuration:

..  code-block:: php

    $this->finisherContext->getFinisherVariableProvider();

The data is stored in :php-short:`TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider` and is accessed
by a user-defined 'finisher identifier' and a custom option value path. The
name of the 'finisher identifier' should consist of the name of the finisher
without the 'Finisher' appendix. If your finisher class extends
:php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`, the finisher
identifier name is stored in the following variable:

..  code-block:: php

    $this->shortFinisherIdentifier

For example, if the name of your finisher class is 'CustomFinisher', this
variable will contain 'Custom'.

There are 4 methods to access and manage data in the `FinisherVariableProvider`:

*   Add data:

    ..  code-block:: php

        $this->finisherContext->getFinisherVariableProvider()->add(
            $this->shortFinisherIdentifier,
            'unique.value.identifier',
            $value
        );

*   Get data:

    ..  code-block:: php

        $this->finisherContext->getFinisherVariableProvider()->get(
            $this->shortFinisherIdentifier,
            'unique.value.identifier',
            'default value'
        );

*   Check the existence of data:

    ..  code-block:: php

        $this->finisherContext->getFinisherVariableProvider()->exists(
            $this->shortFinisherIdentifier,
            'unique.value.identifier'
        );

*   Delete data:

    ..  code-block:: php

        $this->finisherContext->getFinisherVariableProvider()->remove(
            $this->shortFinisherIdentifier,
            'unique.value.identifier'
        );

In this way, finishers can access `FinisherVariableProvider` data programmatically.
However, it is also possible to access `FinisherVariableProvider` data using form configuration.

Assuming that a finisher called 'Custom' adds data to a `FinisherVariableProvider`:

..  code-block:: php

    $this->finisherContext->getFinisherVariableProvider()->add(
        $this->shortFinisherIdentifier,
        'unique.value.identifier',
        'Wouter'
    );

other finishers can access the value 'Wouter' by setting
`{Custom.unique.value.identifier}` in the form definition file.


..  literalinclude:: _codesnippets/_my_form_custom.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _concepts-finishers-customfinisherimplementations-extend-gui:

Add finisher to backend UI
==========================

After registering  a new finisher in the yaml form definition file, you can also
add it to the backend form editor for your backend users ( `formEditor:`
section below) to work with in the GUI:

..  literalinclude:: _codesnippets/_backend-ui.yaml
    :caption: EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
    :linenos:

..  important::

    Make sure to define an  `iconIdentifier` in the `finishersDefinition` of your
    finisher, otherwise the button to remove the finisher from the
    form will not be visible.

..  _concepts-finishers-custom-extend-gui-configuration:

Configuration registration
--------------------------

Place your YAML files in a form set directory — no PHP registration needed:

..  code-block:: none

    EXT:my_extension/
      Configuration/
        Form/
          MyFinisher/
            config.yaml

..  seealso::

    :ref:`concepts-configuration-yaml-autodiscovery`
