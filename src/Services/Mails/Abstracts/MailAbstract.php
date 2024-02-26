<?php

namespace MoloniES\Services\Mails\Abstracts;

class MailAbstract
{
    protected $to;
    protected $subject;
    protected $template;

    protected $extra = '';

    private $headers = ['Content-Type: text/html; charset=UTF-8'];

    protected function run(): void
    {
        $to = $this->to;
        $subject = $this->subject;

        $image = $this->getImage();
        $year = $this->getYear();
        $url = $this->getMoloniUrl();
        $extra = $this->extra;

        // Catch start
        ob_start();

        include MOLONI_ES_TEMPLATE_DIR . $this->template;

        $body = ob_get_clean();
        // Catch end

        $headers = $this->headers;

        wp_mail($to, $subject, $body, $headers);
    }

    protected function getMoloniUrl(): string
    {
        return 'https://www.moloni.es/';
    }

    protected function getImage(): string
    {
        return MOLONI_ES_IMAGES_URL . 'logo-white.png';
    }

    protected function getYear(): string
    {
        return date("Y");
    }
}
