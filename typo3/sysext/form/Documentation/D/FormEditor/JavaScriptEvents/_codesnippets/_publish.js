export function bootstrap(formEditorApp) {
    formEditorApp.getPublisherSubscriber().publish('my/custom/event', ['arg1', 'arg2']);
}
