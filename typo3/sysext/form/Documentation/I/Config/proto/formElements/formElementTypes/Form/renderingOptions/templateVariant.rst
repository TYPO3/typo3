.. include:: /Includes.rst.txt

renderingOptions.templateVariant
--------------------------------

:aspect:`Option path`
    prototypes.<prototypeIdentifier>.formElementsDefinition.Form.renderingOptions.templateVariant

:aspect:`Data type`
    array

:aspect:`Needed by`
    Frontend

:aspect:`Overwritable within form definition`
    Yes

:aspect:`form editor can write this property into the form definition (for prototype 'standard')`
    No

:aspect:`Mandatory`
    No

:aspect:`Default value (for prototype 'standard')`
    ..  code-block:: yaml

        templateVariant: version1

:aspect:`Description`
    Set this option to :yaml:`version2` to use Bootstrap 5 compatible and
    accessible templates.

    ..  deprecated:: 12.0
        Using the legacy form template / partial variants residing in
        :file:`EXT:form/Resources/Private/Frontend/Templates` and
        :file:`EXT:form/Resources/Private/Frontend/Partials` ("version1") is
        deprecated. The legacy templates will be removed in v13.

        **Migration**: Set your form rendering option :yaml:`templateVariant`
        within the form setup from :yaml:`version1` to :yaml:`version2` to use
        the future default templates:

        ..  code-block:: yaml

            prototypes:
              standard:
                formElementsDefinition:
                  Form:
                    renderingOptions:
                      templateVariant: version2

        Adjust your templates / partials to make them compatible with the ones
        stored in :file:`EXT:form/Resources/Private/FrontendVersion2`.
