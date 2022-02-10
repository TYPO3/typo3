let ckeditorPromise: Promise<typeof window.CKEDITOR>|null = null;

function loadScript(url: string): Promise<Event> {
  return new Promise((resolve, reject) => {
    const newScript = document.createElement('script');
    newScript.async = true
    newScript.onerror = reject;
    newScript.onload = (ev: Event) => resolve(ev);
    newScript.src = url;
    document.head.appendChild(newScript);
  });
}

export function loadCKEditor(): Promise<typeof window.CKEDITOR> {
  if (ckeditorPromise === null) {
    const scriptUrl = (import.meta as any).url.replace(/\/[^\/]+\.js/, '/Contrib/ckeditor.js')
    ckeditorPromise = loadScript(scriptUrl).then(() => window.CKEDITOR);
  }
  return ckeditorPromise;
}
