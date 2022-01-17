export enum MimeType {
    TEXT = 'text/plain',
    HTML = 'text/html',
    JSON = 'application/json',
    JS = 'text/javascript',
}

enum HttpMethod {
    GET = 'GET',
    POST = 'POST',
    PUT = 'PUT',
    DELETE = 'DELETE',
}

const TYPE_KEY = '_type_';
const UPDATE = 'update';
const DELETE = 'delete';

export type AjaxCallback = (response: any, type: XMLHttpRequestResponseType) => any;

export class Ajax {
    private static call(
        onload: AjaxCallback,
        method: HttpMethod,
        data: any,
        url: URL,
        type: MimeType,
        accept: MimeType,
    ) {
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            if (this.status == 200) {
                onload(this.response, this.responseType);
            } else {
                alert('Ajax request received status ' + this.status + '.');
            }
        };
        xhr.open(method, url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Content-Type', type);
        xhr.setRequestHeader('Accept', accept);
        if (data) {
            xhr.send(data);
        } else {
            xhr.send();
        }
    }

    public static get(
        onload: AjaxCallback,
        urlStr = '',
        accept = MimeType.JSON,
    ) {
        const url = new URL(urlStr, window.location.href);
        Ajax.call(onload, HttpMethod.GET, null, url, MimeType.JSON, accept);
    }

    public static post(
        onload: AjaxCallback,
        data: any,
        urlStr = '',
        type = MimeType.JSON,
        accept = MimeType.JSON,
    ) {
        const url = new URL(urlStr, window.location.href);
        Ajax.call(onload, HttpMethod.POST, data, url, type, accept);
    }

    public static update(
        onload: AjaxCallback,
        data: any,
        urlStr = '',
        type = MimeType.JSON,
        accept = MimeType.JSON,
    ) {
        const url = new URL(urlStr, window.location.href);
        url.searchParams.set(TYPE_KEY, UPDATE);
        Ajax.call(onload, HttpMethod.POST, data, url, type, accept);
    }

    public static delete(
        onload: AjaxCallback,
        data: any = null,
        urlStr = '',
        accept = MimeType.JSON,
    ) {
        const url = new URL(urlStr, window.location.href);
        url.searchParams.set(TYPE_KEY, DELETE);
        Ajax.call(onload, HttpMethod.POST, data, url, MimeType.JSON, accept);
    }
}
