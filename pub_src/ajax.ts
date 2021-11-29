export class Ajax {
    public static post(
        data: any,
        onload: (response: any, type: XMLHttpRequestResponseType) => any,
        url = '',
        type = 'text/plain'
    ) {
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            if (this.status == 200) {
                onload(this.response, this.responseType);
            } else {
                alert('Ajax request received status ' + this.status + '.');
            }
        };
        xhr.open('POST', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Content-Type', type);
        xhr.setRequestHeader('Accept', type);
        xhr.send(data);
    }
}
