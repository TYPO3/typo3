import { Locator } from '@playwright/test';
import { Modal } from './modal';
import { expect } from 'playwright/test';

export class PageWizard {
  public static readonly CONFIRM_BUTTON_TEXT = 'Create new page';

  public static readonly FINISH_BUTTON_TEXT = 'Finish';

  private static readonly TITLE_FIELD_SELECTOR = '[data-formengine-input-name^="data[pages]"][data-formengine-input-name$="[title]"]';

  static async createDefaultPageAfterDrag(modal: Modal, title: string): Promise<void> {
    await this.ensurePageWizardModalVisible(modal);
    const modalContent = await modal.getModalContent();

    await this.fillTitleField(title, modalContent);

    // go to step 4 (confirmation)
    await this.goToNextStep(modalContent);

    // go to step 5 (finish)
    await this.getButton(modalContent, PageWizard.CONFIRM_BUTTON_TEXT).click();
    await this.isReady(modalContent);

    // finish
    await this.getButton(modalContent, PageWizard.FINISH_BUTTON_TEXT).click();
  }

  static async goToNextStep(modalContent: Locator): Promise<void> {
    await this.getButton(modalContent, 'Next').click();
    await this.isReady(modalContent);
  }

  static getButton(modalContent: Locator, title: string): Locator {
    return modalContent.locator(`.wizard-actions button:not(.disabled):has-text("${title}")`);
  }

  static async ensurePageWizardModalVisible(modal: Modal): Promise<void> {
    const modalContent = await modal.getModalContent();
    await expect(modalContent.locator('typo3-backend-page-wizard')).toHaveCount(1);
    await this.isReady(modalContent);
  }

  static async isReady(modalContent: Locator): Promise<void> {
    await expect(modalContent.locator('.wizard-loader').last()).not.toBeAttached();
  }

  static async fillTitleField(title: string, modalContent: Locator): Promise<void> {
    const input = modalContent.locator(PageWizard.TITLE_FIELD_SELECTOR);

    // FormEngine binds the change listener that copies this control into the
    // field the form submits while it initialises the field, and marks the
    // field done with this attribute. A value typed before that is dropped,
    // and the wizard creates the page with an empty title.
    await expect(input).toHaveAttribute('data-formengine-input-initialized', 'true');

    await input.fill(title);
    await input.press('Tab');
  }

  static async selectDoktype(doktypeString: string, modalContent: Locator): Promise<void> {
    const doktypeCard = modalContent.locator('.form-check-type-card', { hasText: doktypeString });
    await expect(doktypeCard).toHaveCount(1);

    await doktypeCard.click();
  }
}
