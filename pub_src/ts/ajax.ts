export enum MimeType {
    TEXT = 'text/plain',
    HTML = 'text/html',
    JSON = 'application/json',
    JS = 'text/javascript',
}

export class Ajax {
    private static call(
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        method: string,
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
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        url = '',
        accept = MimeType.JSON,
    ) {
        this.call(onload, 'GET', '', url, accept);
    }

    public static put(
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        data = '',
        url = '',
        accept = MimeType.JSON,
    ) {
        this.call(onload, 'PUT', data, url, accept);
    }

    public static post(
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        data = '',
        url = '',
        type = MimeType.JSON,
        accept = MimeType.JSON,
    ) {
        this.call(onload, 'POST', data, url, accept, type);
    }

    public static delete(
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        url = '',
        accept = MimeType.JSON,
    ) {
        this.call(onload, 'DELETE', '', url, accept);
    }
}
