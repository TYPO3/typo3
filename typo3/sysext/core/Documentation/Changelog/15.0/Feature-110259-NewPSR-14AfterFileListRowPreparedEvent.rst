..  include:: /Includes.rst.txt

..  _feature-110259-1784666471:

===========================================================
Feature: #110259 - New PSR-14 AfterFileListRowPreparedEvent
===========================================================

See :issue:`110259`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Filelist\Event\AfterFileListRowPreparedEvent`
has been added to TYPO3 Core. This event is fired after a file or folder row
has been fully prepared for the :guilabel:`Media` module, right before it is
rendered into the final table row markup.

Unlike :php:`\TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent`, which
only allows modifying the action icons in the control column, this event
provides access to the already-rendered data of *every* column in the row
(for example :php:`name`, :php:`size` or any additional metadata column
added via the column selector), as well as the row's HTML tag attributes.
This closes a gap compared to the classic record list in
:guilabel:`Content > Records`, which has offered an equivalent event
(:php:`\TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent`)
for its own rows.

An example event listener could look like this:

..  code-block:: php
    :caption: Example event listener class

    namespace MyVendor\MyExtension\Form\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Filelist\Event\AfterFileListRowPreparedEvent;

    final class AfterFileListRowPreparedEventListener
    {
        #[AsEventListener('my-extension/after-filelist-row-prepared')]
        public function __invoke(AfterFileListRowPreparedEvent $event): void
        {
            $data = $event->getData();
            // Modify the already-rendered value of a specific column
            $data['my_column'] = 'My custom value';
            $event->setData($data);
        }
    }

Impact
======

Extension authors can now decorate or override the rendered value of any
column - not just the action icons - for a file or folder row in the File
List module, without resorting to hooks or class-name-based reflection
workarounds.

..  index:: Backend, PHP-API, ext:filelist
