export enum Mime {
    TEXT = 'text/plain',
    HTML = 'text/html',
    JSON = 'application/json',
    JS = 'text/javascript',
}

export enum HttpMethod {
    GET = 'GET',
    POST = 'POST',
    PUT = 'PUT',
    DELETE = 'DELETE',
}

export const TYPE_KEY = 'requestType';

const UPDATE = 'update';
const DELETE = 'delete';

export type AjaxCallback = (response: any, type: Mime) => any;

export class Ajax {
    public static call(
        onload: AjaxCallback,
        method: HttpMethod,
        data: any,
        url: URL,
        type: Mime,
        accept: Mime,
    ) {
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            if (this.status == 200) {
                const t = this.getResponseHeader('Content-Type');
                onload(this.response, t as Mime);
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
        accept = Mime.JSON,
    ) {
        const url = new URL(urlStr, location.href);
        Ajax.call(onload, HttpMethod.GET, null, url, Mime.JSON, accept);
    }

    public static post(
        onload: AjaxCallback,
        data: any,
        urlStr = '',
        type = Mime.JSON,
        accept = Mime.JSON,
    ) {
        const url = new URL(urlStr, location.href);
        Ajax.call(onload, HttpMethod.POST, data, url, type, accept);
    }

    public static update(
        onload: AjaxCallback,
        data: any,
        urlStr = '',
        type = Mime.JSON,
        accept = Mime.JSON,
    ) {
        const url = new URL(urlStr, location.href);
        url.searchParams.set(TYPE_KEY, UPDATE);
        Ajax.call(onload, HttpMethod.POST, data, url, type, accept);
    }

    public static delete(
        onload: AjaxCallback,
        data: any = null,
        urlStr = '',
        type = Mime.JSON,
        accept = Mime.JSON,
    ) {
        const url = new URL(urlStr, location.href);
        url.searchParams.set(TYPE_KEY, DELETE);
        Ajax.call(onload, HttpMethod.POST, data, url, type, accept);
    }
}
