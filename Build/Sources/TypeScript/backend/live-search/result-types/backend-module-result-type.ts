import LiveSearchConfigurator from '@typo3/backend/live-search/live-search-configurator';
import type { ResultItemInterface } from '@typo3/backend/live-search/element/result/item/item';

export function registerType(type: string) {
  LiveSearchConfigurator.addInvokeHandler(type, 'open_module', (resultItem: ResultItemInterface): void => {
    TYPO3.ModuleMenu.App.showModule(resultItem.extraData.moduleIdentifier);
  });
}
