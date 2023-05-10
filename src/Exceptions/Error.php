<?php

namespace MoloniES\Exceptions;

use Exception;
use MoloniES\Curl;

class Error extends Exception
{
    /** @var array */
    private $request;

    /**
     * Throws a new error with a message and a log from the last request made
     *
     * @param $message
     * @param bool $request
     * @param int $code
     * @param Exception|null $previous
     *
     * @return void
     */
    public function __construct($message, $request = false, $code = 0, Exception $previous = null)
    {
        $this->request = $request ?: Curl::getLog();
        parent::__construct($message, $code, $previous);
    }

    public function showError()
    {
        $message = $this->getDecodedMessage();
        $url = $this->request['url'] ?: '';
        $sent = $this->request['sent'] ?: [];
        $received = $this->request['received'] ?: [];

        include MOLONI_ES_TEMPLATE_DIR . 'Messages/DocumentError.php';
    }

    public function getError()
    {
        ob_start();
        $this->showError();
        return ob_get_clean();
    }

    /**
     * Returns the default error message from construct
     * Or tries to translate the error from Moloni API
     *
     * @return string
     */
    public function getDecodedMessage(): string
    {
        $errorMessage = '<b>' . $this->getMessage() . '</b>';

        if (isset($this->request['received']) && is_array($this->request['received'])) {
            foreach ($this->request['received'] as $line) {
                if (isset($line['description'])) {
                    $errorMessage .= '<br>' . $line['description'];
                } elseif (isset($line[0]['description'])) {
                    $errorMessage .= '<br>' . $line[0]['description'];
                }
            }
        }

        return $errorMessage;
    }

    public function getRequest()
    {
        return $this->request;
    }
}