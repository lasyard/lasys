export enum MimeType {
    TEXT = 'text/plain',
    HTML = 'text/html',
    JSON = 'application/json',
    JS = 'text/javascript',
}

export enum HttpMethod {
    GET = 'GET',
    PUT = 'PUT',
    DELETE = 'DELETE',
    POST = 'POST',
}

export type AjaxCallback = (response: any, type: XMLHttpRequestResponseType) => any;

export class Ajax {
    public static call(
        onload: AjaxCallback,
        method: HttpMethod,
        data: any,
        url: string,
        accept: MimeType,
        type = MimeType.JSON,
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
        Ajax.call(onload, HttpMethod.GET, '', url, accept);
    }

    public static put(
        onload: AjaxCallback,
        data: any,
        url = '',
        accept = MimeType.JSON,
    ) {
        Ajax.call(onload, HttpMethod.PUT, data, url, accept);
    }

    public static post(
        onload: AjaxCallback,
        data: any,
        url = '',
        type = MimeType.JSON,
        accept = MimeType.JSON,
    ) {
        Ajax.call(onload, HttpMethod.POST, data, url, accept, type);
    }

    public static delete(
        onload: AjaxCallback,
        url = '',
        accept = MimeType.JSON,
    ) {
        Ajax.call(onload, HttpMethod.DELETE, '', url, accept);
    }
}
