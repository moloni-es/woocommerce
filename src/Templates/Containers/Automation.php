<form method='POST' action='<?= admin_url('admin.php?page=molonies&tab=automation') ?>' id='formOpcoes'>
    <input type='hidden' value='save' name='action'>
    <div>
        <h2 class="title"><?= __('Automation', 'moloni_es') ?></h2>
        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="invoice_auto"><?= __('Create document automatically', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="invoice_auto" name='opt[invoice_auto]' class='inputOut'>
                        <option value='0' <?= (INVOICE_AUTO === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (INVOICE_AUTO === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('Automatically create document when an order is paid', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync"><?= __('Sync stocks automatically', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync" name='opt[moloni_stock_sync]' class='inputOut'>
                        <option value='0' <?= (MOLONI_STOCK_SYNC === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (MOLONI_STOCK_SYNC === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('Automatic stock synchronization (runs every 5 minutes and updates the stock of items based on Moloni)', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync"><?= __('Sync products', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync" name='opt[moloni_product_sync]' class='inputOut'>
                        <option value='0' <?= (MOLONI_PRODUCT_SYNC === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (MOLONI_PRODUCT_SYNC === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When saving a product in WooCommerce, the plugin will automatically create the product in Moloni or update if it already exists', 'moloni_es') ?></p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title"><?= __('Hooks', 'moloni_es') ?></h2>
        <span>(Moloni -> WooCommerce)</span>
        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="moloni_variants_sync"><?= __('Update products with variants', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_variants_sync" name='opt[moloni_variants_sync]' class='inputOut'>
                        <option value='0' <?= (MOLONI_VARIANTS_SYNC === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (MOLONI_VARIANTS_SYNC === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When updating a product with variants in Moloni, it will update the product in WooCommerce (if product already exists in WooCommerce)', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="hook_product_add"><?= __('Add products', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="hook_product_add" name='opt[hook_product_add]' class='inputOut'>
                        <option value='0' <?= (HOOK_PRODUCT_ADD === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (HOOK_PRODUCT_ADD === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When a product is created in Moloni it will be created in woocommerce (products with variants included)', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="hook_product_update"><?= __('Update products', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="hook_product_update" name='opt[hook_product_update]' class='inputOut'>
                        <option value='0' <?= (HOOK_PRODUCT_UPDATE === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (HOOK_PRODUCT_UPDATE === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When updating a product in Moloni, it will be updated in WooCommerce (only if product already exists in WooCommerce)', 'moloni_es')?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?= __('Fields to sync', 'moloni_es') ?></label>
                </th>
                <td>
                    <fieldset>
                        <input type="checkbox" name="opt[sync_fields_name]" id="name"
                               value="1" <?= (SYNC_FIELDS_NAME === '1' ? 'checked' : '') ?>/><label for="name"><?= __('Name', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_price]" id="price"
                               value="1" <?= (SYNC_FIELDS_PRICE === '1' ? 'checked' : '') ?>/><label for="price"><?= __('Price', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_description]]" id="description"
                               value="1" <?= (SYNC_FIELDS_DESCRIPTION === '1' ? 'checked' : '') ?>/><label for="description"><?= __('Description', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_visibility]" id="visibility"
                               value="1" <?= (SYNC_FIELDS_VISIBILITY === '1' ? 'checked' : '') ?>/><label for="visibility"><?= __('Visibility', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_stock]" id="stock"
                               value="1" <?= (SYNC_FIELDS_STOCK === '1' ? 'checked' : '') ?>/><label for="stock"><?= __('Stock', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_categories]" id="categories"
                               value="1" <?= (SYNC_FIELDS_CATEGORIES === '1' ? 'checked' : '') ?>/><label for="categories"><?= __('Categories', 'moloni_es') ?></label><br/>
                    </fieldset>
                    <p class='description'><?= __('Optional field that will sync when updating/creating a product on your Moloni account', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="hook_stock_update"><?= __('Update stock', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="hook_stock_update" name='opt[hook_stock_update]' class='inputOut'>
                        <option value='0' <?= (HOOK_STOCK_UPDATE === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (HOOK_STOCK_UPDATE === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When a stock movement is created in moloni, the movement will be recreated in WooCommerce (if product exists)', 'moloni_es') ?></p>
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