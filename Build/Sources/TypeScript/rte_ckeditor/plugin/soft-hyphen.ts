import { Core } from '@typo3/ckeditor5-bundle';
import Whitespace from '@typo3/rte-ckeditor/plugin/whitespace';

export default class SoftHyphen extends Core.Plugin {
  static readonly pluginName = 'SoftHyphen';
  static readonly requires = [Whitespace];

  public init(): void {
    console.warn('The TYPO3 CKEditor5 SoftHyphen plugin is deprecated and will be removed with v13. Please use the Whitespace plugin instead.');
  }
}
