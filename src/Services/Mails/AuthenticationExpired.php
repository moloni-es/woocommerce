<?php

namespace MoloniES\Services\Mails;

use MoloniES\Services\Mails\Abstracts\MailAbstract;

class AuthenticationExpired extends MailAbstract
{
    public function __construct($to = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni', 'moloni_es') . ' - ' . __('The Moloni authentication expired', 'moloni_es');
        $this->template = 'Emails/AuthenticationExpired.php';

        $this->run();
    }
}
