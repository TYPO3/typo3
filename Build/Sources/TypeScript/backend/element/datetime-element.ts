/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { html, LitElement, nothing, type TemplateResult, type PropertyValues } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { DateTime, type DurationUnit, type DurationUnits } from 'luxon';
import type { DateConfiguration } from '@typo3/backend/type/date-configuration';

/**
 * Display mode for the time element
 */
export enum DateTimeDisplayMode {
  Relative = 'relative',
  Absolute = 'absolute',
}

/**
 * Web component for displaying timestamps in absolute or relative format
 *
 * Supports ISO 8601 strings and Unix timestamps (seconds).
 * Displays in absolute format by default using TYPO3's configured date format, or in relative format (e.g., "2 minutes ago") when mode="relative".
 *
 * In relative mode, the component automatically updates with adaptive frequency based on elapsed time.
 *
 * The component intelligently calculates the time until the next threshold boundary and updates at that
 * point to ensure the display transitions precisely when Luxon switches time units.
 *
 * ## WCAG Accessibility Compliance (2.1 AA)
 *
 * - **SC 1.3.1 (Level A)**: Semantic `<time>` element with ISO 8601 datetime attribute.
 *   https://www.w3.org/WAI/WCAG21/Understanding/info-and-relationships.html
 *
 * - **SC 4.1.3 (Level AA)**: No aria-live to prevent announcement fatigue with multiple instances.
 *   https://www.w3.org/WAI/WCAG21/Understanding/status-messages.html
 *
 * @example
 * <typo3-backend-datetime datetime="2024-01-15T10:30:00Z"></typo3-backend-datetime>
 * <typo3-backend-datetime datetime="2024-01-15T10:30:00Z" mode="relative"></typo3-backend-datetime>
 * <typo3-backend-datetime datetime="1705324200"></typo3-backend-datetime>
 * <typo3-backend-datetime datetime="2020-01-15T10:30:00Z" mode="relative" threshold-days="365"></typo3-backend-datetime>
 * <typo3-backend-datetime datetime="2024-01-15T10:30:00Z" format="date"></typo3-backend-datetime>
 * <typo3-backend-datetime datetime="2024-01-15T10:30:00Z" format="datetime"></typo3-backend-datetime>
 * <typo3-backend-datetime datetime="2024-01-15T10:30:00Z" format="dd.MM.yyyy HH:mm"></typo3-backend-datetime>
 */
@customElement('typo3-backend-datetime')
export class DateTimeElement extends LitElement {
  @property({ type: String }) datetime: string = '';
  @property() mode: DateTimeDisplayMode = DateTimeDisplayMode.Absolute;
  @property() format: string | null = 'date';
  @property({ type: Number, attribute: 'threshold-days' }) thresholdDays: number = 0;

  @state() private displayTime: string = '';

  private updateTimeoutId: number | null = null;

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.clearUpdateTimeout();
  }

  protected override willUpdate(changedProperties: PropertyValues<this>): void {
    if (changedProperties.has('datetime') || changedProperties.has('mode') || changedProperties.has('format')) {
      this.updateDisplayTime();
      this.clearUpdateTimeout();
      this.startUpdateTimeout();
    }
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    const dateTime = this.parseDateTime(this.datetime);
    const isoDateTime = dateTime?.toISO() || this.datetime;

    const tooltipText = dateTime && this.mode === DateTimeDisplayMode.Relative
      ? this.formatAbsoluteDateTime(this.getLocalizedDateTime(dateTime))
      : '';

    const ariaLabel = tooltipText ? `${this.displayTime || this.datetime}. ${tooltipText}` : null;

    return html`<time
      datetime="${isoDateTime}"
      title="${tooltipText || nothing}"
      aria-label="${ariaLabel || nothing}"
    >${this.displayTime || this.datetime}</time>`;
  }

  private clearUpdateTimeout(): void {
    if (this.updateTimeoutId !== null) {
      window.clearTimeout(this.updateTimeoutId);
      this.updateTimeoutId = null;
    }
  }

  private startUpdateTimeout(): void {
    if (this.mode === DateTimeDisplayMode.Relative) {
      const dateTime = this.parseDateTime(this.datetime);
      const next = dateTime ? this.calculateNextStateChange(dateTime) : null;
      const timeout = next ? Math.abs(next.diffNow('milliseconds').milliseconds) : 1000;
      this.updateTimeoutId = window.setTimeout(() => {
        this.updateTimeoutId = null;
        this.updateDisplayTime();
        this.startUpdateTimeout();
      }, timeout);
    }
  }

  /**
   * Calculates the optimal update interval for relative time display
   *
   * The interval is determined by two factors:
   * 1. Regular update frequency for the current time range
   * 2. Time remaining until the next Luxon toRelative() unit threshold
   *
   * This ensures the display updates both at regular intervals and precisely when
   * Luxon switches between units (seconds → minutes → hours → days).
   */
  private calculateNextStateChange(dateTime: DateTime): DateTime|null {
    const units = ['years', 'months', 'days', 'hours', 'minutes', 'seconds'] as DurationUnits;
    const duration = dateTime.diffNow(units);

    for (const unit of units) {
      const diff = duration.get(unit as DurationUnit);
      if (diff !== 0) {
        const offset = diff < 0 ? -1 : 1;
        return dateTime.minus({ [unit]: diff + offset });
      }
    }

    // If all units are zero, dateTime is equal to *now*
    // => next state change happens in 1 second
    return dateTime.plus({ seconds: 1 });
  }

  private updateDisplayTime(): void {
    const dateTime = this.parseDateTime(this.datetime);
    if (dateTime) {
      this.displayTime = this.mode === DateTimeDisplayMode.Absolute
        ? this.formatAbsoluteTime(dateTime)
        : this.formatRelativeTime(dateTime);
    } else {
      this.displayTime = this.datetime;
    }
  }

  private formatAbsoluteTime(dateTime: DateTime): string {
    const localizedDateTime = this.getLocalizedDateTime(dateTime);
    const configuredFormats = this.getConfiguredDateFormats();

    // Handle special format values that use configured formats
    if (this.format === 'date' && configuredFormats) {
      return localizedDateTime.toFormat(configuredFormats.formats.date);
    }
    if (this.format === 'datetime' && configuredFormats) {
      return localizedDateTime.toFormat(configuredFormats.formats.datetime);
    }

    // Handle custom format string or null
    if (this.format) {
      return localizedDateTime.toFormat(this.format);
    }

    // Fallback when no format is specified and no configured formats available
    return configuredFormats
      ? localizedDateTime.toFormat(configuredFormats.formats.date)
      : localizedDateTime.toLocaleString(DateTime.DATE_SHORT);
  }

  private parseDateTime(dateTimeString: string): DateTime | null {
    if (!dateTimeString) {
      return null;
    }

    // Get configured timezone for parsing timestamps
    const configuredFormats = this.getConfiguredDateFormats();
    const timezone = configuredFormats?.timezone;

    // Try to parse as Unix timestamp (number)
    const timestamp = Number(dateTimeString);
    if (!isNaN(timestamp) && timestamp > 0) {
      const parseOptions = timezone ? { zone: timezone } : {};
      const dt = DateTime.fromSeconds(timestamp, parseOptions);

      if (dt.isValid) {
        return dt;
      }
    }

    // Try to parse as ISO 8601 string
    const dt = DateTime.fromISO(dateTimeString);
    return dt.isValid ? dt : null;
  }

  private getLocalizedDateTime(dateTime: DateTime): DateTime {
    // Get the user's language from TYPO3's <html lang="..."> attribute
    let locale = document.documentElement.lang || 'en';
    // Handle TYPO3's special 'ch' locale mapping (similar to date-time-picker.ts)
    if (locale === 'ch') {
      locale = 'zh';
    }

    // Apply timezone from TYPO3 configuration if available
    const configuredFormats = this.getConfiguredDateFormats();
    const timezone = configuredFormats?.timezone;

    let localizedDateTime = dateTime.setLocale(locale);
    if (timezone) {
      localizedDateTime = localizedDateTime.setZone(timezone);
    }

    return localizedDateTime;
  }

  private formatRelativeTime(dateTime: DateTime): string {
    const localizedDateTime = this.getLocalizedDateTime(dateTime);

    // If threshold is set, check if date is too old for relative format
    if (this.thresholdDays > 0) {
      const daysDiff = Math.abs(DateTime.now().diff(localizedDateTime, 'days').days);
      if (daysDiff > this.thresholdDays) {
        // Date is too old, show absolute date instead using TYPO3's configured format
        return this.formatAbsoluteDate(localizedDateTime);
      }
    }

    const relative = localizedDateTime.toRelative();
    return relative || this.formatAbsoluteDateTime(localizedDateTime);
  }

  private formatAbsoluteDate(dateTime: DateTime): string {
    const localizedDateTime = this.getLocalizedDateTime(dateTime);
    const configuredFormats = this.getConfiguredDateFormats();
    return configuredFormats
      ? localizedDateTime.toFormat(configuredFormats.formats.date)
      : localizedDateTime.toLocaleString(DateTime.DATE_SHORT);
  }

  private formatAbsoluteDateTime(dateTime: DateTime): string {
    const localizedDateTime = this.getLocalizedDateTime(dateTime);
    const configuredFormats = this.getConfiguredDateFormats();
    return configuredFormats
      ? localizedDateTime.toFormat(configuredFormats.formats.datetime)
      : localizedDateTime.toLocaleString(DateTime.DATETIME_SHORT);
  }

  private getConfiguredDateFormats(): DateConfiguration | null {
    try {
      const root = (typeof opener?.top?.TYPO3 !== 'undefined' ? opener.top : top);
      return root.TYPO3.settings.DateConfiguration;
    } catch {
      return null;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-datetime': DateTimeElement;
  }
}
