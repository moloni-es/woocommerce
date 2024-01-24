<?php

if (!defined('ABSPATH')) {
    exit;
}

$row = $row ?? [];
?>

<tr class="product__row"
    data-wc-id="<?= $row['wc_product_id'] ?? 0 ?>"
    data-moloni-id="<?= $row['moloni_product_id'] ?? 0 ?>"
>
    <td class="product__row-name">
        <?= $row['wc_product_object']->get_parent_id() ? ' 	&#8594; ' : '' ?>
        <?= $row['wc_product_object']->get_name(); ?>
    </td>
    <td class="product__row-reference">
        <?= $row['wc_product_object']->get_sku() ?>
    </td>
    <td>
        <?php
        switch ($row['wc_product_object']->get_type()) {
            case 'external':
                echo __('External', 'moloni_es');

                break;
            case 'grouped':
                echo __('Grouped', 'moloni_es');

                break;
            case 'simple':
                echo __('Simple', 'moloni_es');

                break;
            case 'variable':
                echo __('Variable', 'moloni_es');

                break;
            case 'variation':
                echo __('Variation', 'moloni_es');

                break;
            default:
                echo __('Others', 'moloni_es');

                break;
        }
        ?>
    </td>
    <td>
        <?php
        if (empty($row['tool_alert_message'])) {
            echo '---';
        } elseif (is_string($row['tool_alert_message'])) {
            echo $row['tool_alert_message'];
        } elseif (is_array($row['tool_alert_message'])) {
            foreach ($row['tool_alert_message'] as $message) {
                echo $message;
            }
        }
        ?>
    </td>
    <td>
        <?php if (!empty($row['wc_product_link']) || !empty($row['moloni_product_link'])) : ?>
            <div class="dropdown">
                <button type="button" class="dropdown--manager button button-primary">
                    <?= __('Open', 'moloni_es') ?> &#8628;
                </button>
                <div class="dropdown__content">
                    <ul>
                        <?php if (!empty($row['wc_product_link'])) : ?>
                            <li>
                                <a target="_blank" href="<?= $row['wc_product_link'] ?>">
                                    <?= __('Open in WooCommerce', 'moloni_es') ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($row['moloni_product_link'])) : ?>
                            <li>
                                <a target="_blank" href="<?= $row['moloni_product_link'] ?>">
                                    <?= __('Open in Moloni', 'moloni_es') ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </td>
    <td>
        <input type="checkbox" class="checkbox_create_product"
            <?= empty($row['tool_show_create_button']) ? 'disabled' : '' ?>
        >
    </td>
    <td>
        <input type="checkbox" class="checkbox_update_stock_product"
            <?= empty($row['tool_show_update_stock_button']) ? 'disabled' : '' ?>
        >
    </td>
</tr>
