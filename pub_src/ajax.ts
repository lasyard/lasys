export class Ajax {
    private static call(
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        method: string,
        data: any,
        url: string,
        accept: string,
        type = 'text/plain',
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
        xhr.send(data);
    }

    public static post(
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        data = '',
        url = '',
        type = 'text/plain',
        accept = 'text/plain'
    ) {
        this.call(onload, 'POST', data, url, accept, type);
    }

    public static delete(
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        url = '',
        accept = 'text/plain'
    ) {
        this.call(onload, 'DELETE', '', url, accept);
    }
}
