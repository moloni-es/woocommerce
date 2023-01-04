<?php

namespace MoloniES\Helpers;

use WC_Order;
use WC_Meta_Data;

class MoloniOrder
{
    public static function getLastCreatedDocument(WC_Order $wcOrder)
    {
        /** @var WC_Meta_Data[] $documents */
        $documents = $wcOrder->get_meta('_molonies_sent', false);
        $documentId = 0;

        if (!empty($documents)) {
            /** Last item in the array is the latest document */
            $data = end($documents)->get_data();

            $documentId = (int)$data['value'];
        }

        return $documentId;
    }
}