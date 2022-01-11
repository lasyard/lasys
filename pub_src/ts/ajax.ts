export enum MimeType {
    TEXT = 'text/plain',
    HTML = 'text/html',
    JSON = 'application/json',
    JS = 'text/javascript',
}

enum HttpMethod {
    GET = 'GET',
    PUT = 'PUT',
    DELETE = 'DELETE',
    POST = 'POST',
}

export type AjaxCallback = (response: any, type: XMLHttpRequestResponseType) => any;

export class Ajax {
    private static call(
        onload: AjaxCallback,
        method: HttpMethod,
        data: any,
        url: string,
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
        url = '',
        accept = MimeType.JSON,
    ) {
        Ajax.call(onload, HttpMethod.GET, null, url, MimeType.JSON, accept);
    }

    public static put(
        onload: AjaxCallback,
        data: any,
        url = '',
        type = MimeType.JSON,
        accept = MimeType.JSON,
    ) {
        Ajax.call(onload, HttpMethod.PUT, data, url, type, accept);
    }

    public static post(
        onload: AjaxCallback,
        data: any,
        url = '',
        type = MimeType.JSON,
        accept = MimeType.JSON,
    ) {
        Ajax.call(onload, HttpMethod.POST, data, url, type, accept);
    }

    public static delete(
        onload: AjaxCallback,
        url = '',
        accept = MimeType.JSON,
    ) {
        Ajax.call(onload, HttpMethod.DELETE, null, url, MimeType.JSON, accept);
    }
}
