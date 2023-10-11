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

  private getAbortablePromise(
    key: string,
    promise: Promise<T>,
    signal: AbortSignal,
  ): Promise<T> {
    return new Promise<T>((resolve, reject) => {
      const abortListener = () => {
        if (key in this.promises && --this.promises[key].refCount < 1) {
          this.promises[key].abortController.abort();
          delete this.promises[key];
        }
        try {
          signal.throwIfAborted();
        } catch (e) {
          reject(e);
        }
      };

      signal.addEventListener('abort', abortListener, { once: true });
      promise.then(
        (value: T) => {
          signal.removeEventListener('abort', abortListener);
          if (!signal.aborted) {
            resolve(value);
          }
        },
        (e: unknown) => {
          signal.removeEventListener('abort', abortListener);
          reject(e);
        }
      );
    });
  }
}
