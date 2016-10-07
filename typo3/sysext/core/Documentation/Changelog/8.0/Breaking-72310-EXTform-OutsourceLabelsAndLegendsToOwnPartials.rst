
.. include:: ../../Includes.txt

==========================================================================
Breaking: #72310 - EXT:form - Outsource labels and legends to own partials
==========================================================================

See :issue:`72310`

Description
===========

Labels and legends have been outsourced to their own partials. This step is slightly (but thankfully) breaking.

With this change the duplication of code can be avoided. This helps the integrator to customize the labels/ legends with just one small and central override.


Impact
======

No deep impact. If an EXT:form template was overridden, it mostly contains the `label` and/or `legend` tags and acts like it used to do.


Affected Installations
======================

Any installation using EXT:form since TYPO3 7.5.


Migration
=========

Overridden EXT:form partials could be migrated to use the new central label/ legend partials.

Example changes for `Resources/Private/Partials/Default/Show/FlatElements/Checkbox.html`.

Old:

.. code-block:: html

        <label for="{model.additionalArguments.id}">
            {model.additionalArguments.label}
            <f:if condition="{model.mandatoryValidationMessages}">
                <em><f:for each="{model.mandatoryValidationMessages}" as="mandatoryValidationMessage" iteration="iterator">{mandatoryValidationMessage}<f:if condition="{iterator.isLast}"><f:else> - </f:else></f:if></f:for></em>
            </f:if>
            <f:if condition="{model.validationErrorMessages}">
                <strong><f:for each="{model.validationErrorMessages}" as="errorValidationMessage" iteration="iterator">{errorValidationMessage}<f:if condition="{iterator.isLast}"><f:else> - </f:else></f:if></f:for></strong>
            </f:if>
        </label>

New:

.. code-block:: html

        {f:render(partial: '{themeName}/Show/AdditionalElements/Label', arguments: {model: model, themeName: themeName})}

.. index:: Fluid, ext:form
