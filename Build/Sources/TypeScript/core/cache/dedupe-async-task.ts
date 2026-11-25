interface Cache<T> {
  promise: Promise<T>,
  abortController: AbortController,
  refCount: number,
}

/**
 * (abortable) deduping caching strategy for promises
 */
export class DedupeAsyncTask<T> {
  private promises: Record<string, Cache<T>> = {};
  private results: Record<string, T> = {};

  public async get(
    key: string,
    task: (signal: AbortSignal) => Promise<T>,
    signal?: AbortSignal | null,
  ): Promise<T> {
    if (signal?.aborted) {
      signal.throwIfAborted();
    }

    if (key in this.results) {
      return this.results[key];
    }

    const promise = this.getPromise(key, task);
    if (signal) {
      return await this.getAbortablePromise(key, promise, signal);
    }

    return await promise;
  }

  private getPromise(
    key: string,
    task: (signal: AbortSignal) => Promise<T>,
  ): Promise<T> {
    if (key in this.promises) {
      this.promises[key].refCount++;
      return this.promises[key].promise;
    }

    const abortController = new AbortController();
    const refCount = 1;
    const promise = task(abortController.signal)
      .then((value: T): T => {
        this.results[key] = value;
        return value;
      })
      .finally(() => {
        if (key in this.promises) {
          delete this.promises[key];
        }
      });

    this.promises[key] = { promise, abortController, refCount };

    return promise;
  }

  private async getAbortablePromise(
    key: string,
    promise: Promise<T>,
    signal: AbortSignal,
  ): Promise<T> {
    const listenerController = new AbortController();
    const aborted = new Promise<void>((resolve) => {
      signal.addEventListener('abort', (): void => {
        if (key in this.promises && --this.promises[key].refCount < 1) {
          this.promises[key].abortController.abort();
          delete this.promises[key];
        }
        resolve();
      }, { once: true, signal: listenerController.signal });
    });

    try {
      await Promise.race([promise, aborted]);
      signal.throwIfAborted();
      return await promise;
    } finally {
      listenerController.abort();
    }
  }
}
