<?php

namespace MoloniES\Services\Mails;

use MoloniES\Services\Mails\Abstracts\MailAbstract;

class DocumentWarning extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni', 'moloni_es') . ' - ' . __('Moloni document warning', 'moloni_es');
        $this->template = 'Emails/DocumentWarning.php';

        if (!empty($orderName)) {
            $this->extra = __('Order', 'moloni_es') . ': #' . $orderName;
        }

        $this->run();
    }
}
