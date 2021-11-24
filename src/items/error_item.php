<?php
final class ErrorItem
{
    use Getter;

    private $_httpHeaders = [
        'Cache-Control: no-cache, no-store, must-revalidate',
        'Expires: 0',
    ];

    private $_title = 'Error';
    private $_content;

    public function __construct($msg)
    {
        $this->_content = View::renderHtml('error', [
            'message' => $msg
        ]);
    }
}
