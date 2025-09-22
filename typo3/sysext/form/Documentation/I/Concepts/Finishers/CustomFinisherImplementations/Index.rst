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

If you want to make the finisher configurable in the backend UI read
:ref:`here <concepts-finishers-customfinisherimplementations-extend-gui>`.

Finishers are defined as part of a prototype within a
`finishersDefinition`. The property `implementationClassName` is to be
utilized to load the finisher implementation.

..  literalinclude:: _codesnippets/_finishersDefinition.yaml
    :caption: EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml

The custom form definition has to be `registered <https://docs.typo3.org/permalink/typo3-cms-form:concepts-finishers-custom-extend-gui-configuration>`_.

If the finisher requires options, you can define those within the
`options` property. The options will be used as default values and can
be overridden using the `form definition`.

..  _concepts-finishers-custom-default-value:

Define the default value
------------------------

..  literalinclude:: _codesnippets/_CustomFinisher.yaml
    :caption: EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml

..  _concepts-finishers-custom-option-override:

Override the option using the `form definition`
-------------------------------------------------

..  literalinclude:: _codesnippets/_my_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

Each finisher has to be programmed to the interface
:php-short:`TYPO3\CMS\Form\Domain\Finishers\FinisherInterface` and should extend the
class :php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`. In doing so, the logic of the
finisher should start with the method `executeInternal()`.

..  _concepts-finishers-customfinisherimplementations-accessingoptions:

Accessing finisher options
==========================

If your finisher extends :php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`,
you can access your finisher options with the help of the `parseOption()`
method:

..  code-block:: php

    $yourCustomOption = $this->parseOption('yourCustomOption');

`parseOption()` is looking for 'yourCustomOption' in your
`form definition`. If it cannot be found, the method checks

1.  the `prototype` configuration for a default value,

2.  the finisher class itself by searching for a default value within the
    `$defaultOptions` property:

    ..  literalinclude:: _codesnippets/_CustomFinisher.yaml
        :caption: EXT:my_site_package/Classes/Domain/Finishers/CustomFinisher.yaml

If the option cannot be found by processing this fallback chain, `null` is
returned.

If the option is found, the process checks whether the option value will
access :ref:`FormRuntime values <concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`.
If the `FormRuntime` returns a positive result, it is checked whether the
option value :ref:`can access values of preceding finishers <concepts-finishers-customfinisherimplementations-finishercontext-sharedatabetweenfinishers>`.
At the very end, it tries to :ref:`translate the finisher options <concepts-frontendrendering-translation-finishers>`.

..  _concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor:

Accessing form runtime values
=============================

By utilizing a specific notation, finisher options can be populated with
submitted form values (assuming you are using the `parseOption()` method).
You can access values of the `FormRuntime` and thus values of each single
form element by encapsulating the option values with `{}`. If there is a
form element with the `identifier` 'subject', you can access its value
within the finisher configuration. Check out the following example to
get the whole idea.

..  literalinclude:: _codesnippets/_my_form_extended.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  code-block:: php

    // $yourCustomOption contains the value of the form element with the
    // identifier 'subject'
    $yourCustomOption = $this->parseOption('yourCustomOption');

In addition, you can use `{__currentTimestamp}` as a special option value.
It will return the current UNIX timestamp.

..  _concepts-finishers-customfinisherimplementations-finishercontext:

Finisher Context
================

The class :php-short:`TYPO3\CMS\Form\Domain\Finishers\FinisherContext` takes care of
transferring a finisher context to each finisher. Given the finisher is
derived from :php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher` the
finisher context will be available via:

..  code-block:: php

    $this->finisherContext

The method `cancel` prevents the execution of successive finishers:

..  code-block:: php

    $this->finisherContext->cancel();

The method `getFormValues` returns all of the submitted form values.

`getFormValues`:

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
can be easily accessed programmatically or within your configuration:

..  code-block:: php

    $this->finisherContext->getFinisherVariableProvider();

The data is stored within the :php-short:`TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider` and is addressed
by a user-defined 'finisher identifier' and a custom option value path. The
name of the 'finisher identifier' should consist of the name of the finisher
without the potential 'Finisher' appendix. If your finisher is derived from
the class :php-short:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`, the name of
this construct is stored in the following variable:

..  code-block:: php

    $this->shortFinisherIdentifier

For example, if the name of your finisher class is 'CustomFinisher', the
mentioned variable will contain the value 'Custom'.

There are a bunch of methods to access and manage the finisher data:

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

In this way, each finisher can access data programmatically. Moreover, it is
possible to retrieve the data via configuration, provided that a finisher
stores the values within the `FinisherVariableProvider`.

Assuming that a finisher called 'Custom' sets data as follows:

..  code-block:: php

    $this->finisherContext->getFinisherVariableProvider()->add(
        $this->shortFinisherIdentifier,
        'unique.value.identifier',
        'Wouter'
    );

... you are now able to access the value 'Wouter' via `{Custom.unique.value.identifier}`
in any other finisher.


..  literalinclude:: _codesnippets/_my_form_custom.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _concepts-finishers-customfinisherimplementations-extend-gui:

Add finisher to backend UI
==========================

After adding a custom finisher you can also add the finisher to the
form editor GUI to let your backend users configure it visually. Add the
following to the backend yaml setup:

..  literalinclude:: _codesnippets/_backend-ui.yaml
    :caption: EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
    :linenos:

..  important::

    Make sure to define an iconIdentifier within the finishersDefinition of your
    custom finisher; otherwise, the button to remove the finisher from the
    form will not be visible.

..  _concepts-finishers-custom-extend-gui-configuration:

Configuration registration
--------------------------

Make sure the setup file is registered in
either a :file:`EXT:my_extension/ext_localconf.php` file:

..  literalinclude:: _codesnippets/_ext_localconf.php
    :caption: EXT:my_extension/ext_localconf.php

..  seealso::

    `YAML registration for the backend <https://docs.typo3.org/permalink/typo3-cms-form:concepts-configuration-yamlregistration-backend-addtyposcriptsetup>`_

..  versionchanged:: 13.0

    Registration of global TypoScript for the TYPO3 backend has to be done in
    an extensions :file:`ext_localconf.php` using method `ExtensionManagementUtility::addTypoScriptSetup`.

    Former methods of global TypoScript registration are not compatible with
    site sets.
