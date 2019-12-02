.. include:: ../Includes.txt


.. _development:

Development / Background Information
------------------------------------

In this chapter, we want to provide a bit of information on how the linkvalidator works internally.

The procedure for marking the broken links in the RTE is as follow:

#. RTE content is fetched from the database.
   Before it is displayed in the edit form, RTE transformations are performed.
#. The transformation function parses the text and detects links.
#. For each link, a PSR-14 event (:php:`TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent`) is dispatched.
#. If a listener is attached, it may set the link as broken and will set the link as "checked".
#. If a link is detected as broken, RTE will mark it as broken.

Take a look at the :file:`Services.yaml` where the listeners for that event are registered:

.. code-block:: yaml

  TYPO3\CMS\Linkvalidator\EventListener\CheckBrokenRteLinkEventListener:
    tags:
      - name: event.listener
        identifier: 'rte-check-link-external'
        event: TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent
        method: 'checkExternalLink'
      - name: event.listener
        identifier: 'rte-check-link-to-page'
        event: TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent
        method: 'checkPageLink'
      - name: event.listener
        identifier: 'rte-check-link-to-file'
        event: TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent
        method: 'checkFileLink'

File, Page and External Links are checked via these EventListeners.
