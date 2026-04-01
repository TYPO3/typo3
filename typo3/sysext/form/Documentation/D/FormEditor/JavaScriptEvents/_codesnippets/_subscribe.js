export function bootstrap(formEditorApp) {
    const ps = formEditorApp.getPublisherSubscriber();

    // Subscribe – returns a token for later unsubscription
    const token = ps.subscribe('view/ready', (topic, args) => {
        // args is a typed tuple matching the event signature
    });

    // Unsubscribe
    ps.unsubscribe(token);
}
