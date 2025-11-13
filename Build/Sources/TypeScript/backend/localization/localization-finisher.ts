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

import type { TemplateResult } from 'lit';

/**
 * Configuration data passed to finisher instances
 */
export type FinisherConfig = {
  identifier: string;
  module: string;
  data: Record<string, unknown>;
  labels: Record<string, string>;
};

/**
 * Interface for localization finisher implementations
 *
 * Finishers handle the final action after successful localization,
 * such as redirecting to the translated content or reloading the page.
 */
export interface LocalizationFinisherInterface {
  /**
   * Set the finisher configuration
   * Must be called before render() or execute()
   */
  setConfig(config: FinisherConfig): void;

  /**
   * Render the success message/UI for this finisher
   * This is shown after the localization completes successfully
   */
  render(): Promise<TemplateResult>;

  /**
   * Execute the finisher action (redirect, reload, etc.)
   * Called after showing the success message when user clicks "Finish"
   */
  execute(): Promise<void>;
}
