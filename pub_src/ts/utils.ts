export function storageAvailable(type: 'localStorage' | 'sessionStorage') {
    var storage: Storage | null = null;
    try {
        storage = window[type];
        var x = "__storage_test__";
        storage.setItem(x, x);
        storage.removeItem(x);
        return true;
    } catch (e) {
        return (
            e instanceof DOMException
            && (
                // everything except Firefox
                e.name === "QuotaExceededError"
                // Firefox
                || e.name === "NS_ERROR_DOM_QUOTA_REACHED"
            )
            // acknowledge QuotaExceededError only if there's something already stored
            && storage != null
            && storage.length !== 0
        );
    }
}
