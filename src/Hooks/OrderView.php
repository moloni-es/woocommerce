<?php

namespace MoloniES\Hooks;

use WP_Post;
use WC_Order;
use MoloniES\Start;
use MoloniES\Plugin;
use MoloniES\Helpers\MoloniOrder;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the order view
 * There they can create a document for that order or check the document if it was already created
 *
 * @package Moloni\Hooks
 */
class OrderView
{

    public $parent;

    /** @var array */
    private $allowedStatus = ['wc-processing', 'wc-completed'];

    /**
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box()
    {
        $screen = wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box('moloni_add_meta_box', 'Moloni', [$this, 'showMoloniView'], $screen, 'side', 'core');
    }

    function showMoloniView($postOrOrderObject)
    {
        /** @var WC_Order $order */
        $order = ($postOrOrderObject instanceof WP_Post) ? wc_get_order($postOrOrderObject->ID) : $postOrOrderObject;

        if (in_array('wc-' . $order->get_status(), $this->allowedStatus)) {
            $documentId = MoloniOrder::getLastCreatedDocument($order);

            Start::login(true);

            echo '<div style="display: none"><pre>' . print_r($order->get_taxes(), true) . '</pre></div>';

            if ($documentId > 0) {
                echo __('The document has already been generated in Moloni' , 'moloni_es');
                echo '<br>';

                $this->seeDocument($documentId);
                $this->getRecreateDocumentButton($order);
            } elseif ($documentId === -1) {
                echo __('Document marked as generated.' , 'moloni_es');
                echo '<br><br>';

                $this->getDocumentTypeSelect();
                echo '<br><br>';

                $this->getDocumentCreateButton($order, __('Generate again' , 'moloni_es'));
            } else {
                $this->getDocumentCreateButton($order, __('Create' , 'moloni_es'));
                $this->getDocumentTypeSelect();
            }

            echo '<div style="clear:both"></div>';
        } else {
            echo __('The order must be paid for in order to be generated.' , 'moloni_es');
        }
    }

    private function getDocumentTypeSelect()
    {
        $documentType = defined('DOCUMENT_TYPE') ? DOCUMENT_TYPE : '';
        ?>
        <select id="moloni_document_type" style="float:right">
            <option value='invoice' <?= ($documentType === 'invoice' ? 'selected' : '') ?>>
                <?= __('Invoice' , 'moloni_es') ?>
            </option>

            <option value='invoiceReceipt' <?= ($documentType === 'invoiceReceipt' ? 'selected' : '') ?>>
                <?= __('Invoice + Receipt' , 'moloni_es') ?>
            </option>

            <option value='simplifiedInvoice'<?= ($documentType === 'simplifiedInvoice' ? 'selected' : '') ?>>
                <?= __('Simplified Invoice' , 'moloni_es') ?>
            </option>

            <option value='billsOfLading' <?= ($documentType === 'billsOfLading' ? 'selected' : '') ?>>
                <?= __('Bill of lading' , 'moloni_es') ?>
            </option>

            <option value='purchaseOrder' <?= ($documentType === 'purchaseOrder' ? 'selected' : '') ?>>
                <?= __('Purchase Order' , 'moloni_es') ?>
            </option>

            <option value='proFormaInvoice' <?= ($documentType === 'proFormaInvoice' ? 'selected' : '') ?>>
                <?= __('Pro Forma Invoice' , 'moloni_es') ?>
            </option>
        </select>
        <?php
    }

    private function seeDocument($documentId)
    {
        ?>
        <a type="button"
           class="button button-primary"
           target="_BLANK"
           href="<?= esc_url(admin_url('admin.php?page=molonies&action=getInvoice&id=' . $documentId)) ?>"
           style="margin-top: 10px; margin-left: 10px; float:right;"
        >
            <?= __('See document', 'moloni_es') ?>
        </a>

        <?php
    }

    /**
     * Get recreate button
     *
     * @param WC_Order $order
     *
     * @return void
     */
    private function getRecreateDocumentButton($order)
    {
        ?>
        <a type="button"
           class="button"
           target="_BLANK"
           href="<?= esc_url(admin_url('admin.php?page=molonies&action=genInvoice&id=' . $order->get_id())) ?>"
           style="margin-top: 10px; float:right;"
        >
            <?= __('Generate again', 'moloni_es') ?>
        </a>
        <?php
    }

    /**
     * Get create button
     *
     * @param WC_Order $order
     * @param string $text
     *
     * @return void
     */
    private function getDocumentCreateButton($order, $text)
    {
        ?>
        <a type="button"
           class="button-primary"
           target="_BLANK"
           onclick="createMoloniDocument()"
           style="margin-left: 5px; float:right;"
        >
            <?= $text ?>
        </a>

        <script>
            function createMoloniDocument() {
                var redirectUrl = "<?= esc_url(admin_url('admin.php?page=molonies&action=genInvoice&id=' . $order->get_id())) ?>";

                if (document.getElementById('moloni_document_type')) {
                    redirectUrl += '&document_type=' + document.getElementById('moloni_document_type').value;
                }

                window.open(redirectUrl, '_blank')
            }
        </script>
        <?php
    }
}
