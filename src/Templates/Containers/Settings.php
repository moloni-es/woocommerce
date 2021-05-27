<?php use \MoloniES\API\Companies; ?>
<?php use \MoloniES\API\Documents; ?>
<?php use \MoloniES\API\Warehouses; ?>
<?php use \MoloniES\API\MeasurementUnits; ?>
<?php use \MoloniES\API\MaturityDates; ?>
<?php use \MoloniES\API\PaymentMethods; ?>
<?php use \MoloniES\API\Taxes; ?>
<?php use \MoloniES\Model; ?>

<?php
    $variables = ['companyId' => (int) MOLONIES_COMPANY_ID];
    $company = Companies::queryCompany($variables);
?>

<form method='POST' action='<?= admin_url('admin.php?page=molonies&tab=settings') ?>' id='formOpcoes'>
    <input type='hidden' value='save' name='action'>
    <div>
        <h2 class="title"><?= __('Documents' , 'moloni_es') ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="company_slug"><?= __('Company slug','moloni_es') ?></label>
                </th>
                <td>
                    <input id="company_slug" name="opt[company_slug]" type="text"
                           value="<?= $company['data']['company']['data']['slug'] ?>" readonly
                           style="width: 330px;">
                </td>
            </tr>
            <tr>
                <th>
                    <label for="document_type"><?= __('Document type','moloni_es') ?></label>
                </th>
                <td>
                    <select id="document_type" name='opt[document_type]' class='inputOut'>
                        <option value='invoice' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoice' ? 'selected' : '') ?>>
                            <?= __('Invoice' , 'moloni_es') ?>
                        </option>

                        <option value='invoiceReceipt' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoiceReceipt' ? 'selected' : '') ?>>
                            <?= __('Invoice + Receipt' , 'moloni_es') ?>
                        </option>

                        <option value='simplifiedInvoice'<?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'simplifiedInvoice' ? 'selected' : '') ?>>
                            <?= __('Simplified Invoice' , 'moloni_es') ?>
                        </option>

                        <option value='billsOfLading' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'billsOfLading' ? 'selected' : '') ?>>
                            <?= __('Bill of lading' , 'moloni_es') ?>
                        </option>

                        <option value='purchaseOrder' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'purchaseOrder' ? 'selected' : '') ?>>
                            <?= __('Purchase Order','moloni_es') ?>
                        </option>

                        <option value='proFormaInvoice' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'proFormaInvoice' ? 'selected' : '') ?>>
                            <?= __('Pro Forma Invoice' , 'moloni_es') ?>
                        </option>
                    </select>
                    <p class='description'><?= __('Required' , 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="document_status"><?= __('Document status' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="document_status" name='opt[document_status]' class='inputOut'>
                        <option value='0' <?= (defined('DOCUMENT_STATUS') && DOCUMENT_STATUS === '0' ? 'selected' : '') ?>><?= __('Draft' , 'moloni_es') ?></option>
                        <option value='1' <?= (defined('DOCUMENT_STATUS') && DOCUMENT_STATUS === '1' ? 'selected' : '') ?>><?= __('Closed' , 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('Required' , 'moloni_es') . ' ' . __('(Invoice + Receipt cannot be created in draft.)','moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="document_set_id"><?= __('Document set' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="document_set_id" name='opt[document_set_id]' class='inputOut'>
                        <?php $documentSets = Documents::queryDocumentSets($variables);?>
                        <?php foreach ($documentSets as $documentSet) : ?>
                            <option value='<?= $documentSet['documentSetId'] ?>' <?= (defined('DOCUMENT_SET_ID') && (int)DOCUMENT_SET_ID === $documentSet['documentSetId'] ? 'selected' : '') ?>><?= $documentSet['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'><?= __('Required', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="shipping_info"><?= __('Shipping info' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="shipping_info" name='opt[shipping_info]' class='inputOut'>
                        <option value='0' <?= (defined('SHIPPING_INFO') && SHIPPING_INFO === '0' ? 'selected' : '') ?>><?= __('No' , 'moloni_es') ?></option>
                        <option value='1' <?= (defined('SHIPPING_INFO') && SHIPPING_INFO === '1' ? 'selected' : '') ?>><?= __('Yes' , 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('Put shipping info on documents' , 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="email_send"><?= __('Send e-mail' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="email_send" name='opt[email_send]' class='inputOut'>
                        <option value='0' <?= (defined('EMAIL_SEND') && EMAIL_SEND === '0' ? 'selected' : '') ?>><?= __('No' , 'moloni_es') ?></option>
                        <option value='1' <?= (defined('EMAIL_SEND') && EMAIL_SEND === '1' ? 'selected' : '') ?>><?= __('Yes' , 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('The document is only sent to the customer if it is inserted as closed' , 'moloni_es') ?></p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title"><?= __('Products' , 'moloni_es') ?></h2>
        <table class="form-table">
            <tbody>

            <?php $warehouses = Warehouses::queryWarehouses($variables); ?>
            <?php if (count($warehouses) > 1): ?>
                <tr>

                    <th>
                        <label for="moloni_product_warehouse"><?= __('Warehouse' , 'moloni_es') ?></label>
                    </th>
                    <td>
                        <select id="moloni_product_warehouse" name='opt[moloni_product_warehouse]' class='inputOut'>
                            <option value='0'><?= __('Default warehouse' , 'moloni_es') ?></option>
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option value='<?= $warehouse['warehouseId'] ?>' <?= (defined('MOLONI_PRODUCT_WAREHOUSE') && (int)MOLONI_PRODUCT_WAREHOUSE === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                    <?= $warehouse['name'] ?> (<?= $warehouse['number'] ?>)
                                </option>
                            <?php endforeach; ?>

                        </select>
                        <p class='description'><?= __('Required', 'moloni_es') ?></p>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <th>
                    <label for="measure_unit_id"><?= __('Measurement unit' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="measure_unit_id" name='opt[measure_unit]' class='inputOut'>
                        <?php $measurementUnits = MeasurementUnits::queryMeasurementUnits($variables); ?>
                        <?php if (is_array($measurementUnits)): ?>
                            <?php foreach ($measurementUnits as $measurementUnit) : ?>
                                <option value='<?= $measurementUnit['measurementUnitId'] ?>' <?= (defined('MEASURE_UNIT') && (int)MEASURE_UNIT === $measurementUnit['measurementUnitId'] ? 'selected' : '') ?>><?= $measurementUnit['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Required' , 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="sync_images"><?= __('Sync images', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="sync_images" name='opt[sync_images]' class='inputOut'>
                        <option value='0' <?= (defined('SYNC_IMAGES') && SYNC_IMAGES === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (defined('SYNC_IMAGES') && SYNC_IMAGES === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When creating products in Moloni, the image will also be synchronized', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="tax_id"><?= __('Products tax' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="tax_id" name='opt[tax_id]' class='inputOut'>
                        <option value='0' <?= (defined('TAX_ID') && (int)TAX_ID === 0 ? 'selected' : '') ?>><?= __('Use WooCommerce value' , 'moloni_es') ?></option>
                        <?php $taxes = Taxes::queryTaxes($variables); ?>
                        <?php if (is_array($taxes)): ?>
                            <?php foreach ($taxes as $tax) : ?>
                                <option value='<?= $tax['taxId'] ?>' <?= (defined('TAX_ID') && (int)TAX_ID === $tax['taxId'] ? 'selected' : '') ?>><?= $tax['name'] . ' ('.$tax['value'].'%)'?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= sprintf(__('Use if you do not have tax applied to %s' , 'moloni_es'),'products') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="tax_id_shipping"><?= __('Shipping tax' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="tax_id_shipping" name='opt[tax_id_shipping]' class='inputOut'>
                        <option value='0' <?= (defined('TAX_ID_SHIPPING') && (int)TAX_ID_SHIPPING === 0 ? 'selected' : '') ?>><?= __('Use WooCommerce value'  , 'moloni_es') ?></option>
                        <?php $taxes = Taxes::queryTaxes($variables); ?>
                        <?php if (is_array($taxes)): ?>
                            <?php foreach ($taxes as $tax) : ?>
                                <option value='<?= $tax['taxId'] ?>' <?= (defined('TAX_ID_SHIPPING') && (int)TAX_ID_SHIPPING === $tax['taxId'] ? 'selected' : '') ?>><?= $tax['name'] . ' ('.$tax['value'].'%)'?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= sprintf(__('Use if you do not have tax applied to %s' , 'moloni_es'),'shipping') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason"><?= __('Exemption reason' , 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="exemption_reason" name="opt[exemption_reason]" type="text"
                           value="<?= (defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '') ?>"
                           class="inputOut">
                    <p class='description'><?= __('Will be used if items do not have tax' , 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason_shipping"><?= __('Shipping exemption reason' , 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="exemption_reason_shipping" name="opt[exemption_reason_shipping]" type="text"
                           value="<?= (defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '') ?>"
                           class="inputOut">
                    <p class='description'><?= __('Will be used if shipping does not have tax' , 'moloni_es') ?></p>
                </td>
            </tr>
            </tbody>
        </table>

        <h2 class="title"><?= __('Customer\'s'  , 'moloni_es') ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="client_prefix"><?= __('Customer\'s number prefix' , 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="client_prefix" name="opt[client_prefix]" type="text"
                           value="<?= (defined('CLIENT_PREFIX') ? CLIENT_PREFIX : '') ?>"
                           class="inputOut" onchange="prefixPreview()">
                    <div style="max-width: 80vw ;overflow:hidden;" >
                        <a id="prefix_preview"><?= sprintf(__('(Example: %s1)' , 'moloni_es'),(defined('CLIENT_PREFIX') ? CLIENT_PREFIX : '')) ?></a>
                    </div>
                    <p class='description'><?= __('Prefix used when creating customer\'s' , 'moloni_es') ?></p>
                </td>
            </tr>

            <script>
                function prefixPreview() {
                    var label = document.getElementById("prefix_preview");
                    var input = document.getElementById("client_prefix");

                    label.innerText = '(Example: ' + input.value + '1)';
                }
            </script>

            <tr>
                <th>
                    <label for="maturity_date_id"><?= __('Maturity date' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="maturity_date_id" name='opt[maturity_date]' class='inputOut'>
                        <option value='0' <?= (defined('MATURITY_DATE') && (int)MATURITY_DATE === 0 ? 'selected' : '') ?>><?= __('Choose an option' , 'moloni_es') ?></option>
                        <?php $maturityDates = MaturityDates::queryMaturityDates($variables); ?>
                        <?php if (is_array($maturityDates)): ?>
                            <?php foreach ($maturityDates as $maturityDate) : ?>
                                <option value='<?= $maturityDate['maturityDateId'] ?>' <?= (defined('MATURITY_DATE') && (int)MATURITY_DATE === $maturityDate['maturityDateId'] ? 'selected' : '') ?>><?= $maturityDate['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Default maturity date for customer\'s' , 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="payment_method_id"><?= __('Payment method' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="payment_method_id" name='opt[payment_method]' class='inputOut'>
                        <option value='0' <?= (defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === 0 ? 'selected' : '') ?>><?= __('Choose an option' , 'moloni_es') ?></option>
                        <?php $paymentMethods = PaymentMethods::queryPaymentMethods($variables); ?>
                        <?php if (is_array($paymentMethods)): ?>
                            <?php foreach ($paymentMethods as $paymentMethod) : ?>
                                <option value='<?= $paymentMethod['paymentMethodId'] ?>' <?= (defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === $paymentMethod['paymentMethodId'] ? 'selected' : '') ?>><?= $paymentMethod['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Default payment method for customer\'s' , 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="vat_field"><?= __('Customer VAT' , 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="vat_field" name='opt[vat_field]' class='inputOut'>
                        <option value='' <?= (defined('VAT_FIELD') && VAT_FIELD === '' ? 'selected' : '') ?>><?= __('Choose an option' , 'moloni_es') ?></option>
                        <?php $customFields = Model::getCustomFields(); ?>
                        <?php if (is_array($customFields)): ?>
                            <?php foreach ($customFields as $customField) : ?>
                                <option value='<?= $customField['meta_key'] ?>' <?= (defined('VAT_FIELD') && VAT_FIELD === $customField['meta_key'] ? 'selected' : '') ?>><?= $customField['meta_key'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Custom field associated with the customer\'s VAT'  , 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?= __('Save changes' , 'moloni_es') ?>">
                </td>
            </tr>

            </tbody>
        </table>

    </div>
</form>