..  include:: /Includes.rst.txt

..  _feature-108726-1769073579:

=================================================================================================
Feature: #108726 - Add PSR-14 Events ModifyRenderedContentAreaEvent and ModifyRenderedRecordEvent
=================================================================================================

See :issue:`108726`

Description
===========

With the :php:`\TYPO3\CMS\Fluid\Event\ModifyRenderedContentAreaEvent`, developers can
intercept the rendering of content areas in Fluid templates to modify the output.
This depends on content areas being rendered with the new :html:`<f:render.contentArea>`
ViewHelper in Fluid templates, see
:ref:`Introduce Fluid f:render.contentArea ViewHelper <feature-108726-1769071158>`.

With the :php:`\TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent`, developers can
intercept the rendering of individual records in Fluid templates to modify the output.
This depends on records being rendered with the new :html:`<f:render.contentArea>` or :html:`<f:render.record>`
ViewHelpers in Fluid templates, see
:ref:`Introduce Fluid f:render.record ViewHelper <feature-108726-1769503907>`.

Note that any alterations will be output as-is and will not be escaped. If you
process insecure content within an event listener, be sure to escape it properly,
e.g. by applying :php:`htmlspecialchars()` to it.

Example
=======

An example event listener could look like this:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ModifyRenderedContentEventListener.php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Fluid\Event\ModifyRenderedContentAreaEvent;
    use TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent;

    final class ModifyRenderedContentEventListener
    {
        #[AsEventListener]
        public function modifyContentArea(ModifyRenderedContentAreaEvent $event): void
        {
            $content = 'before area<hr />'. $event->getRenderedContentArea() . '<hr />after area';
            $event->setRenderedContentArea($content);
        }

        #[AsEventListener]
        public function modifyRecord(ModifyRenderedRecordEvent $event): void
        {
            $content = 'before record<hr />'. $event->getRenderedRecord() . '<hr />after record';
            $event->setRenderedRecord($content);
        }
    }


Impact
======

The new events can be used by extension authors to enhance the output of
content areas and records rendered in themes.


..  index:: Frontend, ext:fluid
