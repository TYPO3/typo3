<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\Widgets\Interfaces;

/**
 * The WidgetInterface is the base interface for all kind of widgets.
 * All widgets must implement this interface.
 * It contains the methods which are required for all widgets.
 */
interface WidgetInterface
{
    /**
     * Returns the unique identifer of a widget
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Returns the title of a widget, this is used for the widget selector
     * @return string
     */
    public function getTitle(): string;

    /**
     * Returns the description of a widget, this is used for the widget selector
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns the icon identifier of a widget, this is used for the widget selector
     * @return string
     */
    public function getIconIdentifier(): string;

    /**
     * Returns the height of a widget in rows (1-6)
     * @return int
     */
    public function getHeight(): int;

    /**
     * Returns the width of a widget in columns (1-4)
     * @return int
     */
    public function getWidth(): int;

    /**
     * This method returns the content of a widget. The returned markup will be delivered
     * by an AJAX call and will not be escaped.
     * Be aware of XSS and ensure that the content is well encoded.
     * @return string
     */
    public function renderWidgetContent(): string;

    /**
     * This method returns additional CSS classes which should be added to the rendered widget
     * @return string
     */
    public function getAdditionalClasses(): string;
}
