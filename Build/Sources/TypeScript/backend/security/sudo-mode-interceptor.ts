import type { RequestMiddleware, RequestHandler } from '@typo3/core/ajax/ajax-request-types';

export const sudoModeInterceptor: RequestMiddleware = async (request: Request, next: RequestHandler): Promise<Response> => {
  // Requests are not immutable, therefore we clone to be able to re-submit exactly the same request later on
  const requestClone = request.clone();
  const response = await next(request);
  if (response.status === 422) {
    const { initiateSudoModeModal } = await import('@typo3/backend/security/element/sudo-mode');
    const data = await response.json();
    try {
      await initiateSudoModeModal(data.sudoModeInitialization);
    } catch {
      // sudo mode was aborted
      return response;
    }
    return next(requestClone);
  }
  return response;
};
