import ClientStorage from '@typo3/backend/storage/client';

export interface UsageMetrics {
  lastUsed: number;
  count: number;
}

export interface UsageMap {
  [recordIdentifier: string]: UsageMetrics;
}

export interface RecordUsageStoreData {
  usage: UsageMap;
}

const RECORD_USAGE_STORE_KEY_PREFIX = 'record-usage/';

export class RecordUsageStore {
  constructor(
    private readonly storeName: string
  ) {
  }

  public track(recordIdentifier: string): void {
    const storeData: RecordUsageStoreData = this.load();

    storeData.usage[recordIdentifier] = {
      lastUsed: Date.now(),
      count: (storeData.usage[recordIdentifier]?.count ?? 0) + 1
    };

    this.save(storeData);
  }

  public getUsage(): UsageMap {
    return this.load().usage;
  }

  private load(): RecordUsageStoreData {
    const storageEntry = ClientStorage.get(this.localStorageKey());
    if (storageEntry === null) {
      return {
        usage: {}
      };
    }
    return this.removeOldItems(JSON.parse(storageEntry));
  }

  private save(usageData: RecordUsageStoreData): void {
    ClientStorage.set(this.localStorageKey(), JSON.stringify(usageData));
  }

  private removeOldItems(usageData: RecordUsageStoreData): RecordUsageStoreData {
    const oneMonthAgo = Date.now() - (30 * 24 * 60 * 60 * 1000);
    usageData.usage = Object.fromEntries(
      Object.entries(usageData.usage).filter(([, item]) => item.lastUsed >= oneMonthAgo)
    ) as UsageMap;

    return usageData;
  }

  private localStorageKey(): string {
    return RECORD_USAGE_STORE_KEY_PREFIX + this.storeName;
  }
}
