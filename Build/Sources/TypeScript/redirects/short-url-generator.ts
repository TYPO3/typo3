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

import DocumentService from '@typo3/core/document-service';
import FormEngine from '@typo3/backend/form-engine';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Notification from '@typo3/backend/notification';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';

/**
 * Handles the "Generate Short URL" field control
 */
class ShortUrlGenerator {
  private controlElement: HTMLAnchorElement = null;
  private humanReadableField: HTMLInputElement = null;
  private sourceHostField: HTMLInputElement = null;
  private debounceTimer: ReturnType<typeof setTimeout> = null;
  private hasDuplicateError: boolean = false;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = <HTMLAnchorElement>document.getElementById(controlElementId);
      this.humanReadableField = <HTMLInputElement>document.querySelector(
        'input[data-formengine-input-name="' + this.controlElement.dataset.itemName.replace('[short_url]', '[source_path]') + '"]',
      );
      this.sourceHostField = <HTMLInputElement>document.querySelector(
        'input[data-formengine-input-name="' + this.controlElement.dataset.itemName.replace('[short_url]', '[source_host]') + '"]',
      );
      this.controlElement.addEventListener('click', this.generateShortUrl.bind(this));
      this.humanReadableField.addEventListener('input', this.debouncedValidate.bind(this));
      if (this.sourceHostField) {
        this.sourceHostField.addEventListener('input', this.debouncedValidate.bind(this));
      }
    });
  }

  private debouncedValidate(): void {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
    }
    this.debounceTimer = setTimeout((): void => {
      this.validateShortUrl();
    }, 400);
  }

  private validateShortUrl(): void {
    const sourcePath = this.humanReadableField.value;
    const sourceHost = this.sourceHostField ? this.sourceHostField.value : '';

    if (sourcePath === '') {
      this.setFieldError(false);
      return;
    }

    (new AjaxRequest(TYPO3.settings.ajaxUrls.short_url_validate)).post({
      source_host: sourceHost,
      source_path: sourcePath,
    })
      .then(async (response: AjaxResponse): Promise<void> => {
        const resolvedBody = await response.resolve();
        const isDuplicate = !resolvedBody.isUnique;
        this.setFieldError(isDuplicate);
        if (isDuplicate && !this.hasDuplicateError && resolvedBody.message) {
          Notification.warning(resolvedBody.message);
        }
        this.hasDuplicateError = isDuplicate;
      })
      .catch(() => {
        // Silently ignore validation errors — server-side hook will catch duplicates on save
      });
  }

  private setFieldError(hasError: boolean): void {
    if (hasError) {
      // Apply duplicate error markers to both fields
      const fields = [this.humanReadableField, this.sourceHostField].filter(Boolean);
      for (const field of fields) {
        field.classList.add(FormEngineValidation.errorClass);
        field.setAttribute('aria-invalid', 'true');
      }
      this.humanReadableField.closest(FormEngineValidation.markerSelector)
        ?.querySelector(FormEngineValidation.labelSelector)
        ?.classList.add(FormEngineValidation.errorClass);
      FormEngineValidation.markParentTab(this.humanReadableField, false);
      this.humanReadableField.closest('form')?.dispatchEvent(
        new CustomEvent('t3-formengine-postfieldvalidation', {
          detail: { field: this.humanReadableField, isValid: false },
          cancelable: false,
          bubbles: true,
        }),
      );
    } else {
      // Remove our duplicate error markers from source_host (not managed by FormEngine)
      if (this.sourceHostField) {
        this.sourceHostField.classList.remove(FormEngineValidation.errorClass);
        this.sourceHostField.removeAttribute('aria-invalid');
      }
      // Let FormEngine re-evaluate built-in validations (e.g. "required") for source_path
      FormEngineValidation.validateField(this.humanReadableField);
    }
  }

  private generateShortUrl(e: Event): void {
    e.preventDefault();

    const sourceHost = this.sourceHostField ? this.sourceHostField.value : '';

    // Generate new Short URL with collision avoidance
    (new AjaxRequest(TYPO3.settings.ajaxUrls.short_url_generate)).post({
      source_host: sourceHost,
    })
      .then(async (response: AjaxResponse): Promise<void> => {
        const resolvedBody = await response.resolve();
        if (resolvedBody.success === true) {
          this.humanReadableField.value = resolvedBody.shortUrl;
          // Manually dispatch "change" to enable FormEngine handling (instead of manually calling "updateInputField()").
          // This way custom modules are also triggered when listening on this event.
          this.humanReadableField.dispatchEvent(new Event('change'));
          // Finally validate and mark the field as changed
          FormEngineValidation.validateField(this.humanReadableField);
          FormEngine.markFieldAsChanged(this.humanReadableField);
          // Clear any conflict state — server already verified uniqueness
          this.setFieldError(false);
        } else {
          Notification.warning(resolvedBody.message || 'No Short URL was generated');
        }
      })
      .catch(() => {
        Notification.error('Short URL could not be generated');
      });
  }
}

export default ShortUrlGenerator;
