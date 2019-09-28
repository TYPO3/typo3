import * as $ from 'jquery';
import NotificationService = require('TYPO3/CMS/Backend/Notification');
import DeferredAction = require('TYPO3/CMS/Backend/ActionButton/DeferredAction');

class EventHandler {
  public constructor() {
    document.addEventListener(
      'typo3:redirects:slugChanged',
      (evt: CustomEvent) => this.onSlugChanged(evt.detail),
    );
  }

  public onSlugChanged(detail: any): void {
    let actions: any = [];
    const correlations = detail.correlations;

    if (detail.autoUpdateSlugs) {
      actions.push({
        label: TYPO3.lang['notification.redirects.button.revert_update'],
        action: new DeferredAction(() => this.revert([
          correlations.correlationIdSlugUpdate,
          correlations.correlationIdRedirectCreation,
        ])),
      });
    }
    if (detail.autoCreateRedirects) {
      actions.push({
        label: TYPO3.lang['notification.redirects.button.revert_redirect'],
        action: new DeferredAction(() => this.revert([
          correlations.correlationIdRedirectCreation,
        ])),
      });
    }

    let title = TYPO3.lang['notification.slug_only.title'];
    let message = TYPO3.lang['notification.slug_only.message'];
    if (detail.autoCreateRedirects) {
      title = TYPO3.lang['notification.slug_and_redirects.title'];
      message = TYPO3.lang['notification.slug_and_redirects.message'];
    }
    NotificationService.info(
      title,
      message,
      0,
      actions,
    );
  }

  private revert(correlationIds: string[]): void {
    $.ajax({
      url: TYPO3.settings.ajaxUrls.redirects_revert_correlation,
      data: {
        correlation_ids: correlationIds,
      },
    }).done((json: any) => {
      if (json.status === 'ok') {
        NotificationService.success(json.title, json.message);
      }
      if (json.status === 'error') {
        NotificationService.error(json.title, json.message);
      }
    }).fail(() => {
      NotificationService.error(TYPO3.lang.redirects_error_title, TYPO3.lang.redirects_error_message);
    });
  }
}

export = new EventHandler();
