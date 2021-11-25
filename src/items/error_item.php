<?php
final class ErrorItem
{
    use Getter;

    private $_httpHeaders = Sys::NO_CACHE_HEADERS;

    private $_title = 'Error';
    private $_content;

    public function __construct($msg)
    {
        $this->_content = View::renderHtml('error', [
            'message' => $msg
        ]);
    }
}
