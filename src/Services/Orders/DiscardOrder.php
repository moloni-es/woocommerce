<?php

namespace MoloniES\Services\Orders;

use MoloniES\Storage;
use WC_Order;

class DiscardOrder
{
    private $order;

    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    public function run()
    {
        $this->order->add_meta_data('_molonies_sent', '-1');
        $this->order->add_order_note(__('Order was discarded', 'moloni_es'));
        $this->order->save();
    }

    public function saveLog()
    {
        $message = sprintf(
            __('Order was discarded (%s)', 'moloni_es'),
            $this->order->get_order_number()
        );

        Storage::$LOGGER->info($message, [
            'tag' => 'service:order:discard',
            'order_id' => $this->order->get_id()
        ]);
    }
}
