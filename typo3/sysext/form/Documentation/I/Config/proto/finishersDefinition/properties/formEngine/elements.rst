.. include:: /Includes.rst.txt

Every array key must match to the related finisher option name.
For example, the - :ref:`"[Redirect] finisher"<typo3.cms.form.prototypes.\<prototypeidentifier>.finishersdefinition.redirect>` has the option - :ref:`"pageUid"<typo3.cms.form.prototypes.\<prototypeIdentifier>.finishersdefinition.redirect.options.pageuid>`.
If you want to make the ``pageUid`` overwritable within the ``form plugin``, then an array key ``pageUid`` has to exists within ``TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.<finisherIdentifier>.FormEngine.elements``.
The configuration within ``TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.FormEngine.elements.pageUid`` must follow the TCA syntax.
