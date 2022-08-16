export function onContentLoad(fun: () => any) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fun);
    } else {
        fun();
    }
}

export function onLoad(fun: () => any) {
    window.addEventListener('load', fun);
}

export function copyToClipboard(e: HTMLInputElement) {
    if (e.value) {
        e.focus();
        e.select();
        document.execCommand('copy');
        return true;
    }
    return false;
}
