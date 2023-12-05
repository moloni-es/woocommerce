<?php
if (!defined('ABSPATH')) {
    exit;
}

use MoloniES\API\Companies;
use MoloniES\API\Countries;
use MoloniES\API\DocumentSets;
use MoloniES\API\MaturityDates;
use MoloniES\API\MeasurementUnits;
use MoloniES\API\PaymentMethods;
use MoloniES\API\Warehouses;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\DocumentStatus;
use MoloniES\Enums\DocumentTypes;
use MoloniES\Enums\Languages;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Model;

try {
    $company = Companies::queryCompany();
    $documentSets = DocumentSets::queryDocumentSets();
    $paymentMethods = PaymentMethods::queryPaymentMethods();
    $maturityDates = MaturityDates::queryMaturityDates();
    $warehouses = Warehouses::queryWarehouses();
    $measurementUnits = MeasurementUnits::queryMeasurementUnits();

    $countries = Countries::queryCountries([
        'options' => [
            'defaultLanguageId' => Languages::ES
        ]
    ]);
} catch (APIExeption $e) {
    $e->showError();
    return;
}
?>

<form method='POST' action='<?= admin_url('admin.php?page=molonies&tab=settings') ?>' id='formOpcoes'>
    <input type='hidden' value='saveSettings' name='action'>
    <div>
        <!-- Documents -->
        <h2 class="title">
            <?= __('Document', 'moloni_es') ?>
        </h2>
        <table class="form-table">
            <tbody>
            <!-- Slug -->
            <tr>
                <th>
                    <label for="company_slug"><?= __('Company slug', 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="company_slug" name="opt[company_slug]" type="text"
                           value="<?= $company['data']['company']['data']['slug'] ?>" readonly
                           style="width: 330px;">
                </td>
            </tr>

            <!-- Document type -->
            <tr>
                <th>
                    <label for="document_type"><?= __('Document type', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="document_type" name='opt[document_type]' class='inputOut'>
                        <?php
                        $documentType = '';

                        if (defined('DOCUMENT_TYPE') && !empty(DOCUMENT_TYPE)) {
                            $documentType = DOCUMENT_TYPE;
                        }
                        ?>

                        <?php foreach (DocumentTypes::getForRender() as $id => $name) : ?>
                            <option value='<?= $id ?>' <?= ($documentType === $id ? 'selected' : '') ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?= __('Required', 'moloni_es') ?>
                    </p>
                </td>
            </tr>

            <!-- Document status -->
            <tr>
                <th>
                    <label for="document_status"><?= __('Document status', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="document_status" name='opt[document_status]' class='inputOut'>
                        <?php
                        $documentStatus = 0;

                        if (defined('DOCUMENT_STATUS') && !empty(DOCUMENT_STATUS)) {
                            $documentStatus = (int)DOCUMENT_STATUS;
                        }
                        ?>

                        <option
                            value='0' <?= ($documentStatus === DocumentStatus::DRAFT ? 'selected' : '') ?>><?= __('Draft', 'moloni_es') ?></option>
                        <option
                            value='1' <?= ($documentStatus === DocumentStatus::CLOSED ? 'selected' : '') ?>><?= __('Closed', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('Required', 'moloni_es') . ' ' . __('(Invoice + Receipt cannot be created in draft)', 'moloni_es') ?></p>
                </td>
            </tr>

            <!-- Bill of lading -->
            <tr id="create_bill_of_lading_line">
                <th>
                    <label for="create_bill_of_lading"><?= __('Create bill of lading', 'moloni_es') ?></label>
                </th>
                <td>
                    <?php
                    $createBillOfLading = 0;

                    if (defined('CREATE_BILL_OF_LADING')) {
                        $createBillOfLading = (int)CREATE_BILL_OF_LADING;
                    }
                    ?>

                    <select id="create_bill_of_lading" name='opt[create_bill_of_lading]' class='inputOut'>
                        <option value='0' <?= ($createBillOfLading === 0 ? 'selected' : '') ?>>
                            <?= __('No', 'moloni_es') ?>
                        </option>
                        <option value='1' <?= ($createBillOfLading === 1 ? 'selected' : '') ?>>
                            <?= __('Yes', 'moloni_es') ?>
                        </option>
                    </select>
                    <p class='description'><?= __('Choose if you want to create a Bill of Lading associated with the main document', 'moloni_es') ?></p>
                </td>
            </tr>

            <!-- Document set -->
            <tr>
                <th>
                    <label for="document_set_id"><?= __('Document set', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="document_set_id" name='opt[document_set_id]' class='inputOut'>
                        <?php foreach ($documentSets as $documentSet) : ?>
                            <option
                                value='<?= $documentSet['documentSetId'] ?>' <?= (defined('DOCUMENT_SET_ID') && (int)DOCUMENT_SET_ID === $documentSet['documentSetId'] ? 'selected' : '') ?>><?= $documentSet['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'><?= __('Required', 'moloni_es') ?></p>
                </td>
            </tr>

            <!-- Shipping info -->
            <tr>
                <th>
                    <label for="shipping_info"><?= __('Shipping info', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="shipping_info" name='opt[shipping_info]' class='inputOut'>
                        <option
                            value='0' <?= (defined('SHIPPING_INFO') && SHIPPING_INFO === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option
                            value='1' <?= (defined('SHIPPING_INFO') && SHIPPING_INFO === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('Put shipping info on documents', 'moloni_es') ?></p>
                </td>
            </tr>

            <!-- Load address -->
            <tr id="load_address_line" style="display: none;">
                <th>
                    <label for="load_address"><?= __('Load address', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="load_address" name='opt[load_address]' class='inputOut'>
                        <?php $activeLoadAddress = defined('LOAD_ADDRESS') ? (int)LOAD_ADDRESS : 0; ?>

                        <option
                            value='0' <?= ($activeLoadAddress === 0 ? 'selected' : '') ?>><?= __('Company address', 'moloni_es') ?></option>
                        <option
                            value='1' <?= ($activeLoadAddress === 1 ? 'selected' : '') ?>><?= __('Custom', 'moloni_es') ?></option>
                    </select>

                    <div class="custom-address__wrapper" id="load_address_custom_line">
                        <div class="custom-address__line">
                            <input name="opt[load_address_custom_address]" id="load_address_custom_address"
                                   value="<?= defined('LOAD_ADDRESS_CUSTOM_ADDRESS') ? LOAD_ADDRESS_CUSTOM_ADDRESS : '' ?>"
                                   placeholder="Morada" type="text" class="inputOut">
                        </div>
                        <div class="custom-address__line">
                            <input name="opt[load_address_custom_code]" id="load_address_custom_code"
                                   value="<?= defined('LOAD_ADDRESS_CUSTOM_CODE') ? LOAD_ADDRESS_CUSTOM_CODE : '' ?>"
                                   placeholder="Código Postal" type="text" class="inputOut inputOut--sm">
                            <input name="opt[load_address_custom_city]" id="load_address_custom_city"
                                   value="<?= defined('LOAD_ADDRESS_CUSTOM_CITY') ? LOAD_ADDRESS_CUSTOM_CITY : '' ?>"
                                   placeholder="Localidade" type="text" class="inputOut inputOut--sm">
                        </div>
                        <div class="custom-address__line">
                            <select id="load_address_custom_country" name="opt[load_address_custom_country]"
                                    class="inputOut inputOut--sm">
                                <?php $activeCountry = defined('LOAD_ADDRESS_CUSTOM_COUNTRY') ? (int)LOAD_ADDRESS_CUSTOM_COUNTRY : 0; ?>

                                <option value='0' <?= ($activeCountry === 0 ? 'selected' : '') ?>><?=
                                    __('Choose an option', 'moloni_es') ?>
                                </option>

                                <?php foreach ($countries['data']['countries']['data'] as $country) : ?>
                                    <option
                                        value='<?= $country['countryId'] ?>' <?= $activeCountry === (int)$country['countryId'] ? 'selected' : '' ?>>
                                        <?= $country['title'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <p class='description'><?= __('Load address used in shipping informations', 'moloni_es') ?></p>
                </td>
            </tr>

            <!-- Send e-mail -->
            <tr>
                <th>
                    <label for="email_send"><?= __('Send e-mail', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="email_send" name='opt[email_send]' class='inputOut'>
                        <option
                            value='0' <?= (defined('EMAIL_SEND') && EMAIL_SEND === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option
                            value='1' <?= (defined('EMAIL_SEND') && EMAIL_SEND === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('The document is only sent to the customer if it is inserted as closed', 'moloni_es') ?></p>
                </td>
            </tr>

            <!-- Listagem de encomendas -->
            <tr>
                <th>
                    <label for="moloni_show_download_column"><?= __('WooCommerce order list', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_show_download_column" name='opt[moloni_show_download_column]' class='inputOut'>
                        <?php $moloniShowDownloadColumn = defined('MOLONI_SHOW_DOWNLOAD_COLUMN') ? (int)MOLONI_SHOW_DOWNLOAD_COLUMN : Boolean::NO; ?>

                        <option value='0' <?= ($moloniShowDownloadColumn === Boolean::NO ? 'selected' : '') ?>>
                            <?= __('No', 'moloni_es') ?>
                        </option>
                        <option value='1' <?= ($moloniShowDownloadColumn === Boolean::YES ? 'selected' : '') ?>>
                            <?= __('Yes', 'moloni_es') ?>
                        </option>
                    </select>
                    <p class='description'><?= __('Add, in WooCommerce, a column in the order list with fast download of PDF documents', 'moloni_es') ?></p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Products -->
        <h2 class="title">
            <?= __('Document', 'moloni_es') ?>
            -
            <?= __('Products', 'moloni_es') ?>
        </h2>
        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="moloni_product_warehouse"><?= __('Warehouse', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_product_warehouse" name='opt[moloni_product_warehouse]' class='inputOut'>
                        <option value='0'>
                            <?= __('Default warehouse', 'moloni_es') ?>
                        </option>

                        <?php $moloniProductWarehouse = defined('MOLONI_PRODUCT_WAREHOUSE') ? (int)MOLONI_PRODUCT_WAREHOUSE : Boolean::NO; ?>

                        <optgroup label="<?= __('Warehouses', 'moloni_es') ?>">
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option
                                    value='<?= $warehouse['warehouseId'] ?>' <?= ($moloniProductWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                    <?= $warehouse['name'] ?> (<?= $warehouse['number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <p class='description'><?= __('Warehouse used in documents', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="measure_unit_id"><?= __('Measurement unit', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="measure_unit_id" name='opt[measure_unit]' class='inputOut'>
                        <?php if (is_array($measurementUnits)): ?>
                            <?php foreach ($measurementUnits as $measurementUnit) : ?>
                                <option
                                    value='<?= $measurementUnit['measurementUnitId'] ?>' <?= (defined('MEASURE_UNIT') && (int)MEASURE_UNIT === $measurementUnit['measurementUnitId'] ? 'selected' : '') ?>><?= $measurementUnit['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Required', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason"><?= __('Exemption reason', 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="exemption_reason" name="opt[exemption_reason]" type="text"
                           value="<?= (defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '') ?>"
                           class="inputOut">
                    <p class='description'><?= __('Will be used if items do not have tax', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason_shipping"><?= __('Shipping exemption reason', 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="exemption_reason_shipping" name="opt[exemption_reason_shipping]" type="text"
                           value="<?= (defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '') ?>"
                           class="inputOut">
                    <p class='description'><?= __('Will be used if shipping does not have tax', 'moloni_es') ?></p>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Customer -->
        <h2 class="title">
            <?= __('Document', 'moloni_es') ?>
            -
            <?= __('Customer\'s', 'moloni_es') ?>
        </h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="customer_language"><?= __('Customer language', 'moloni_es') ?></label>
                </th>
                <td>
                    <?php
                    $customerLanguage = 0;

                    if (defined('CUSTOMER_LANGUAGE')) {
                        $customerLanguage = (int)CUSTOMER_LANGUAGE;
                    }
                    ?>

                    <select id="customer_language" name='opt[customer_language]' class='inputOut'>
                        <option value='0' <?= ($customerLanguage === 0 ? 'selected' : '') ?>>
                            <?= __('Automatic', 'moloni_es') ?>
                        </option>
                        <optgroup label="<?= __('Language', 'moloni_es')?>">
                            <option value='<?= Languages::PT ?>' <?= ($customerLanguage === Languages::PT ? 'selected' : '') ?>>
                                <?= __('Portuguese', 'moloni_es') ?>
                            </option>
                            <option value='<?= Languages::ES ?>' <?= ($customerLanguage === Languages::ES ? 'selected' : '') ?>>
                                <?= __('Spanish', 'moloni_es') ?>
                            </option>
                            <option value='<?= Languages::EN ?>' <?= ($customerLanguage === Languages::EN ? 'selected' : '') ?>>
                                <?= __('English', 'moloni_es') ?>
                            </option>
                        </optgroup>
                    </select>

                    <p class='description'>
                        <?= __('Default language for customer\'s', 'moloni_es') ?>
                    </p>

                </td>
            </tr>

            <tr>
                <th>
                    <label for="client_prefix"><?= __('Customer\'s number prefix', 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="client_prefix" name="opt[client_prefix]" type="text"
                           value="<?= (defined('CLIENT_PREFIX') ? CLIENT_PREFIX : '') ?>"
                           class="inputOut">
                    <div style="max-width: 80vw ;overflow:hidden;">
                        <a id="prefix_preview">
                            (<?= __('Example', 'moloni_es') . ': ' . (defined('CLIENT_PREFIX') ? CLIENT_PREFIX : '') ?>)
                        </a>
                    </div>
                    <p class='description'><?= __('Prefix used when creating customer\'s', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="maturity_date_id"><?= __('Maturity date', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="maturity_date_id" name='opt[maturity_date]' class='inputOut'>
                        <option
                            value='0' <?= (defined('MATURITY_DATE') && (int)MATURITY_DATE === 0 ? 'selected' : '') ?>><?= __('Choose an option', 'moloni_es') ?></option>
                        <?php if (is_array($maturityDates)): ?>
                            <?php foreach ($maturityDates as $maturityDate) : ?>
                                <option
                                    value='<?= $maturityDate['maturityDateId'] ?>' <?= (defined('MATURITY_DATE') && (int)MATURITY_DATE === $maturityDate['maturityDateId'] ? 'selected' : '') ?>><?= $maturityDate['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Default maturity date for customer\'s', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="payment_method_id"><?= __('Payment method', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="payment_method_id" name='opt[payment_method]' class='inputOut'>
                        <option
                            value='0' <?= (defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === 0 ? 'selected' : '') ?>><?= __('Choose an option', 'moloni_es') ?></option>
                        <?php if (is_array($paymentMethods)): ?>
                            <?php foreach ($paymentMethods as $paymentMethod) : ?>
                                <option
                                    value='<?= $paymentMethod['paymentMethodId'] ?>' <?= (defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === $paymentMethod['paymentMethodId'] ? 'selected' : '') ?>><?= $paymentMethod['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Default payment method for customer\'s', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="vat_field"><?= __('Customer VAT', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="vat_field" name='opt[vat_field]' class='inputOut'>
                        <option
                            value='' <?= (defined('VAT_FIELD') && VAT_FIELD === '' ? 'selected' : '') ?>><?= __('Choose an option', 'moloni_es') ?></option>
                        <?php $customFields = Model::getCustomFields(); ?>
                        <?php if (is_array($customFields)): ?>
                            <?php foreach ($customFields as $customField) : ?>
                                <option
                                    value='<?= $customField['meta_key'] ?>' <?= (defined('VAT_FIELD') && VAT_FIELD === $customField['meta_key'] ? 'selected' : '') ?>><?= $customField['meta_key'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'>
                        <?= __('Custom field associated with the customer\'s VAT', 'moloni_es') ?>
                        <br>
                        <?=
                        sprintf(
                            __('If you don\'t have any fields VAT yet, you can add the available plugin <a target="_blank" href="%s">here.</a>', 'moloni_es'),
                            'https://wordpress.org/plugins/contribuinte-checkout/'
                        );
                        ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Advanced -->
        <h2 class="title">
            <?= __('Advanced', 'moloni_es') ?>
        </h2>
        <table class="form-table">
            <tbody>

            <!-- Send alert e-mail -->
            <tr>
                <th>
                    <label for="alert_email"><?= __('Alert e-mail', 'moloni_es') ?></label>
                </th>
                <td>
                    <input id="alert_email" name="opt[alert_email]" type="text"
                           value="<?= (defined('ALERT_EMAIL') ? ALERT_EMAIL : '') ?>"
                           class="inputOut">

                    <p class='description'><?= __('Receive alerts for when an error occurs (document creation/authentication lost).', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?= __('Save changes', 'moloni_es') ?>">
                </td>
            </tr>

            </tbody>
        </table>
    </div>
</form>

<script>
    Moloni.Settings.init({
        example: "<?= __('Example', 'moloni_es') ?>"
    });
</script>
