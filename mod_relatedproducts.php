<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
* @author    GeoKolo
* @copyright GeoKolo
* @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/classes/RelatedProductModel.php');

class Mod_RelatedProducts extends Module
{
    public function __construct()
    {
        $this->name = 'mod_relatedproducts';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'GeoKolo';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Related Products');
        $this->description = $this->l('Create and manage your related products.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        Configuration::updateValue('MOD_RELATEDPRODUCTS_TYPE', 0);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_MAX', 5);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_ISIZE', 2); // home_default, small_default, medium_default, large_default, cart_default
        Configuration::updateValue('MOD_RELATEDPRODUCTS_THEME', 1);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_REVERSE', 0);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_RANDOMIZE', false);

        Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_PAGER', false);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_LOOP', false);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_AUTO', false);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL', false);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_WIDTH', 250);
        Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_MARGIN', 10);

        if (!parent::install()
            || !$this->registerHook('actionFrontControllerSetMedia')
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->registerHook('displayAdminProductsExtra')
            || !$this->registerHook('actionProductDelete')
            || !$this->registerHook('actionProductAdd')
            || !$this->registerHook('displayFooterProduct')
            || !$this->registerHook('displayFooterCart')) {
                return false;
        }

        require_once(dirname(__FILE__).'/sql/install.php');

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_TYPE');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_MAX');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_ISIZE');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_THEME');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_REVERSE');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_RANDOMIZE');

        Configuration::deleteByName('MOD_RELATEDPRODUCTS_SL_PAGER');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_SL_LOOP');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_SL_AUTO');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_SL_WIDTH');
        Configuration::deleteByName('MOD_RELATEDPRODUCTS_SL_MARGIN');

        require_once(dirname(__FILE__).'/sql/uninstall.php');
        return parent::uninstall();
    }

    public function hookActionFrontControllerSetMedia()
    {
        //$controller = Tools::getValue('controller');

        //if (($controller === 'product' || $controller === 'cart') && Configuration::get('MOD_RELATEDPRODUCTS_THEME') == 3) {
        if ('product' === $this->context->controller->php_self || 'cart' === $this->context->controller->php_self) {
            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-style',
                'modules/' . $this->name . '/views/css/style.css',
                [
                  'media' => 'all',
                  'priority' => 1000,
                ]
            );

            if (version_compare(_PS_VERSION_, '1.7.0.0 ', '>=') && Configuration::get('MOD_RELATEDPRODUCTS_THEME') == 3) {
                $this->context->controller->addJqueryPlugin('bxslider');
                
                $this->context->controller->registerJavascript(
                    'module-' . $this->name . '-lib',
                    'modules/' . $this->name . '/views/js/slider-config.js',
                    [
                        'attribute' => 'async',
                        'priority' => 200,
                    ]
                );
            }
        }
    }

    public function hookdisplayBackOfficeHeader()
    {
        $controller = Tools::getValue('controller');
        if ($controller === 'AdminProducts') {
            $this->context->controller->addCSS(($this->_path).'views/css/admin.css', 'all');
            $this->context->controller->addJquery();
            $this->context->controller->addJS(($this->_path).'views/js/script-17.min.js');

            $this->context->controller->addJqueryUI('ui.sortable');
        }
    }

    public function ajaxProcessAddProduct()
    {
        $id_product = (int)Tools::getValue('id_product');
        $related = new RelatedProductModel($id_product, $this->context->shop->id);
        $related->id_related = Tools::getValue('id_related');
        $related->position = $related->getHighestPosition() + 1;
        if (!$related->id_related) {
            die('Error id_related');
        }

        if ($related->addRelatedProduct()) {
            if (Configuration::get('MOD_RELATEDPRODUCTS_REVERSE')) {
                $other_related = array();
                $related_pros = $related->getRelatedProducts(true, 0, true);
                foreach ($related_pros as $product) {
                    if ($product['id_product'] != $related->id_related) {
                        $other_related[] = $product['id_product'];
                    }
                }
                foreach ($other_related as $id_new_related) {
                    $reverse_related = new RelatedProductModel($related->id_related, $this->context->shop->id);
                    $reverse_related->id_related = $id_new_related;
                    $reverse_related->position = $reverse_related->getHighestPosition() + 1;
                    $reverse_related->addRelatedProduct();
                }
            }
            die('Ok');
        }

        die('Error');
    }

    public function ajaxProcessDeleteProduct()
    {
        $id_product = (int)Tools::getValue('id_product');
        $related = new RelatedProductModel($id_product, $this->context->shop->id);
        $related->id_related = Tools::getValue('id_related');
        if (!$related->id_related) {
            die('Error id_related');
        }
        if ($related->deleteOne()) {
            die('Ok');
        }
        die('Error');
    }

    public function ajaxProcessUpdatePositions()
    {
        $id_product = (int)Tools::getValue('id_product');
        $related_ids = Tools::getValue('related_ids');
        $result = true;

        $related = new RelatedProductModel($id_product, $this->context->shop->id);
        foreach ($related_ids as $position => $id_related) {
            $related->id_related = $id_related;
            $related->position = $position;
            $result &= $related->updatePosition();
        }
        if ($result) {
            die('Ok');
        }
        die('Error');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = Tools::getValue('id_product') ? (int)Tools::getValue('id_product') : (int)$params['id_product'];
        if ($id_product) {
            $stores = Shop::getContextListShopID();
            if (count($stores) > 1) {
                return $this->l('Please select shop!');
            }

            $id_shop = (isset($stores[0]) ? $stores[0] : 1);
            $relateds = new RelatedProductModel($id_product, $id_shop);
            $product_data = $relateds->getRelatedProducts(Configuration::get('MOD_RELATEDPRODUCTS_REVERSE'), 0, true);
            $module_ajax_url = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&module_name='.$this->name.'&ajax=true';

            $remote_url = 'ajax_products_list.php?forceJson=1&excludeVirtuals=1&limit=20';
            if (version_compare(_PS_VERSION_, '1.6.0.0 ', '>=')) {
                $remote_url = $this->context->link->getAdminLink('', false).$remote_url;
            }

            $this->smarty->assign(
                array(
                    'related_products' => $product_data,
                    'id_product' => $id_product,
                    'module_ajax_url' => $module_ajax_url,
                    'id_shop' => $id_shop,
                    'remote_url' => $remote_url,
                    'link_to_setting' => $this->context->link->getAdminLink('AdminModules').'&configure=mod-relatedproducts'
                )
            );

            return $this->display(__FILE__, '/views/templates/admin/adminproducttab.tpl');
        } else {
            return $this->l('Please save this product!');
        }
    }

    public function hookActionProductAdd($params)
    {
        $id_product = (int)$params['product']->id;
        if ($id_product) {
            $related = new RelatedProductModel($id_product, $this->context->shop->id);
            $related->id_related = Tools::getValue('id_related');

            $related->position = $related->getHighestPosition() + 1;
            if (!$related->id_related) {
                return false;
            }
    
            if ($related->addRelatedProduct()) {
                if (Configuration::get('MOD_RELATEDPRODUCTS_REVERSE')) {
                    $other_related = array();
                    $related_pros = $related->getRelatedProducts(true, 0, true);
                    foreach ($related_pros as $product) {
                        if ($product['id_product'] != $related->id_related) {
                            $other_related[] = $product['id_product'];
                        }
                    }
                    foreach ($other_related as $id_new_related) {
                        $reverse_related = new RelatedProductModel($related->id_related, $this->context->shop->id);
                        $reverse_related->id_related = $id_new_related;
                        $reverse_related->position = $reverse_related->getHighestPosition() + 1;
                        $reverse_related->addRelatedProduct();
                    }
                }
                return true;
            }
    
            return false;
        }
    }

    public function hookActionProductDelete($params)
    {
        $id_product = (int)$params['product']->id;
        if ($id_product) {
            Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'mod_relatedproducts`
                    WHERE `id_product` = '.(int)$id_product.' OR `id_related` = '.(int)$id_product);
        }
    }

    public function hookDisplayLeftColumn($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        if ($id_product) {
            return $this->hookDisplayFooterProduct($params);
        }
    }

    public function hookDisplayRightColumn($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        if ($id_product) {
            return $this->hookDisplayFooterProduct($params);
        }
    }

    private function prepareForDisplaing($id_product, $id_shop)
    {
        $relateds = new RelatedProductModel($id_product, $id_shop);
        $product_data = $relateds->getRelatedProducts(Configuration::get('MOD_RELATEDPRODUCTS_REVERSE'), (int)Configuration::get('MOD_RELATEDPRODUCTS_TYPE'));
        if (Configuration::get('MOD_RELATEDPRODUCTS_RANDOMIZE')) {
            shuffle($product_data);
        }
        if ((int)Configuration::get('MOD_RELATEDPRODUCTS_MAX') > 0) {
            $product_data = array_slice($product_data, 0, (int)Configuration::get('MOD_RELATEDPRODUCTS_MAX'));
        }

        $products = array();
        $imagestype = new ImageType(Configuration::get('MOD_RELATEDPRODUCTS_ISIZE'));

        if (version_compare(_PS_VERSION_, '1.7.0.0 ', '>=')) {
            // prepare the products
            $products = $this->prepareMultipleProductsForTemplate(
                $product_data
            );
        }

        if (!empty($products)) {
            $this->context->smarty->assign(array(
                'slide_pager' => Configuration::get('MOD_RELATEDPRODUCTS_SL_PAGER'),
                'slide_infiniteLoop' => Configuration::get('MOD_RELATEDPRODUCTS_SL_LOOP'),
                'slide_auto' => Configuration::get('MOD_RELATEDPRODUCTS_SL_AUTO'),
                'slide_hideControlOnEnd' => Configuration::get('MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL'),
                'slide_slideWidth' => Configuration::get('MOD_RELATEDPRODUCTS_SL_WIDTH'),
                'slide_slideMargin' => Configuration::get('MOD_RELATEDPRODUCTS_SL_MARGIN'),
                'products' => $products,
                'homeSize' => Image::getSize($imagestype->name),
                'image_size' => $imagestype->name,
                ));

            if (Configuration::get('MOD_RELATEDPRODUCTS_THEME') == 3) {
                return $this->display(__file__, '/views/templates/hooks/ps17_productfooter_slide.tpl');
            } elseif (Configuration::get('MOD_RELATEDPRODUCTS_THEME') == 2) {
                return $this->display(__file__, '/views/templates/hooks/ps17_productfooter_modern.tpl');
            } else {
                return $this->display(__file__, '/views/templates/hooks/ps17_productfooter_classic.tpl');
            }
        }

        return null;
    }

    public function hookDisplayFooterProduct($params)
    {
        $id_product = (int)Tools::getValue('id_product');
        $id_shop = 1;
        if (!is_null(Shop::getContextShopID())) {
            $id_shop = Shop::getContextShopID();
        }

        return $this->prepareForDisplaing($id_product, $id_shop);
    }

    public function hookDisplayFooterCart($params)
    {
        $productIds = $this->getViewedProductIds();
        
        if(count($productIds) <= 0){
            return;
        }

        $id_product = $productIds[mt_rand(0, count($productIds) - 1)];
       
        $id_shop = 1;
        if (!is_null(Shop::getContextShopID())) {
            $id_shop = Shop::getContextShopID();
        }

        return $this->prepareForDisplaing($id_product, $id_shop);
    }

    protected function getViewedProductIds()
    {
        $viewedProductsIds = array_reverse(explode(',', $this->context->cookie->viewed));
        // if (null !== $this->currentProductId && in_array($this->currentProductId, $viewedProductsIds)) {
        //     $viewedProductsIds = array_diff($viewedProductsIds, array($this->currentProductId));
        // }

        $existingProducts = $this->getExistingProductsIds();
        $viewedProductsIds = array_filter($viewedProductsIds, function ($entry) use ($existingProducts) {
            return in_array($entry, $existingProducts);
        });

        return array_slice($viewedProductsIds, 0, (int) (Configuration::get('SK_PRODUCTS_VIEWED_NBR')));
    }

    /**
    * @return array the list of active product ids.
    */
    private function getExistingProductsIds()
    {
        $existingProductsQuery = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT p.id_product
            FROM ' . _DB_PREFIX_ . 'product p
            WHERE p.active = 1'
        );

        return array_map(function ($entry) {
            return $entry['id_product'];
        }, $existingProductsQuery);
    }

    private function getFactory()
    {
        return new ProductPresenterFactory($this->context, new TaxConfiguration());
    }

    protected function getProductPresentationSettings()
    {
        return $this->getFactory()->getPresentationSettings();
    }

    protected function getProductPresenter()
    {
        return $this->getFactory()->getPresenter();
    }

    private function prepareProductForTemplate(array $rawProduct)
    {
        $product = (new ProductAssembler($this->context))
            ->assembleProduct($rawProduct)
            ;

        $presenter = $this->getProductPresenter();
        $settings = $this->getProductPresentationSettings();

        return $presenter->present(
            $settings,
            $product,
            $this->context->language
        );
    }

    protected function prepareMultipleProductsForTemplate(array $products)
    {
        return array_map(array($this, 'prepareProductForTemplate'), $products);
    }

    public function getContent()
    {
        return $this->postProcess().$this->displayInfos().$this->renderForm().$this->renderForm2().$this->renderForm3();
    }

    private function displayInfos()
    {
        $this->context->smarty->assign(array(
                'link_to_product' => $this->context->link->getAdminLink('AdminProducts')
        ));

        return $this->display(__FILE__, '/views/templates/admin/infos.tpl');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitRPPConf')) {
            Configuration::updateValue('MOD_RELATEDPRODUCTS_TYPE', Tools::getValue('MOD_RELATEDPRODUCTS_TYPE'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_MAX', Tools::getValue('MOD_RELATEDPRODUCTS_MAX'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_ISIZE', Tools::getValue('MOD_RELATEDPRODUCTS_ISIZE'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_THEME', Tools::getValue('MOD_RELATEDPRODUCTS_THEME'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_REVERSE', Tools::getValue('MOD_RELATEDPRODUCTS_REVERSE'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_RANDOMIZE', Tools::getValue('MOD_RELATEDPRODUCTS_RANDOMIZE'));

            $this->_clearCache('/views/templates/hooks/productfooter.tpl');
            return $this->displayConfirmation($this->l('The settings have been updated.'));
        } elseif (Tools::isSubmit('submitSlideConf')) {
            Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_PAGER', Tools::getValue('MOD_RELATEDPRODUCTS_SL_PAGER'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_LOOP', Tools::getValue('MOD_RELATEDPRODUCTS_SL_LOOP'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_AUTO', Tools::getValue('MOD_RELATEDPRODUCTS_SL_AUTO'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL', Tools::getValue('MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_WIDTH', Tools::getValue('MOD_RELATEDPRODUCTS_SL_WIDTH'));
            Configuration::updateValue('MOD_RELATEDPRODUCTS_SL_MARGIN', Tools::getValue('MOD_RELATEDPRODUCTS_SL_MARGIN'));

            $this->_clearCache('/views/templates/hooks/productfooter.tpl');
            return $this->displayConfirmation($this->l('The slide settings have been updated.'));
        } elseif (Tools::isSubmit('submitImportConf')) {
            // Import related products from CSV file.
            if (!empty($_FILES['inport_file']) && $_FILES['inport_file']['error'] == 0) {
                $file = $_FILES['inport_file']['tmp_name'];
                $count = 0;

                if (($handle = fopen($file, 'r')) !== false) {
                    while (($data = fgetcsv($handle)) !== false) {
                        if (empty($data) || !isset($data[0]) || !is_numeric($data[0])) {
                            continue;
                        }
                        $related = new RelatedProductModel($data[0], $data[2]);
                        $related->id_related = $data[1];
                        if (isset($data[3])) {
                            $related->position = $data[3];
                        }

                        if ($related->addRelatedProduct()) {
                            $count++;
                        }
                    }
                    fclose($handle);
                }
                return $this->displayConfirmation(sprintf($this->l('There are %s related products been added/updated.'), $count));
            }
        }

        return '';
    }

    public function renderForm()
    {
        $type_radio = 'radio';
        if (version_compare(_PS_VERSION_, '1.6.0.0 ', '>=')) {
            $type_radio = 'switch';
        }

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Type to display?'),
                        'name' => 'MOD_RELATEDPRODUCTS_TYPE',
                        'required' => true,
                        'class' => 't',
                        'br' => true,
                        'values' => array(
                            array(
                                'id' => 'level_0',
                                'value' => 0,
                                'label' => $this->l('Manually')
                            ),
                            array(
                                'id' => 'level_1',
                                'value' => 1,
                                'label' => $this->l('Automatically (using tags)')
                            ),
                            array(
                                'id' => 'level_4',
                                'value' => 4,
                                'label' => $this->l('Automatically (using tags) - Custom')
                            ),
                            array(
                                'id' => 'level_2',
                                'value' => 2,
                                'label' => $this->l('Category')
                            ),
                            array(
                                'id' => 'level_3',
                                'value' => 3,
                                'label' => $this->l('Category (included parents)')
                            ),
                            array(
                                'id' => 'level_5',
                                'value' => 5,
                                'label' => $this->l('All categories')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Max of related products'),
                        'name' => 'MOD_RELATEDPRODUCTS_MAX',
                        'size' => 50
                    ),
                    array(
                        'type' => $type_radio,
                        'label' => $this->l('Randomize?'),
                        'name' => 'MOD_RELATEDPRODUCTS_RANDOMIZE',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                                    array(
                                        'id' => 'randomize_on',
                                        'value' => true,
                                        'label' => $this->l('Enabled')
                                    ),
                                    array(
                                        'id' => 'randomize_off',
                                        'value' => false,
                                        'label' => $this->l('Disabled')
                                    )
                        ),
                        'desc' => $this->l('Show products according to the random principle.')
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Size of thumbnails'), // home_default, small_default, medium_default, large_default, cart_default
                        'name' => 'MOD_RELATEDPRODUCTS_ISIZE',
                        'class' => 't',
                        'options' => array(
                                'query' => ImageType::getImagesTypes('products'),
                                'id' => 'id_image_type',
                                'name' => 'name'
                        ),
                        'desc' => $this->l('Only applies to "Classic" style')
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Display Style'), // Classic, Modern
                        'name' => 'MOD_RELATEDPRODUCTS_THEME',
                        'class' => 't',
                        'options' => array(
                                'query' => array(
                                        array(
                                            'id' => 1,
                                            'title' => 'Classic'
                                        ),
                                        array(
                                            'id' => 2,
                                            'title' => 'Modern'
                                        ),
                                        array(
                                            'id' => 3,
                                            'title' => 'Slide (NEW)'
                                        )
                                ),
                                'id' => 'id',
                                'name' => 'title'
                        )
                    ),
                    array(
                        'type' => $type_radio,
                        'label' => $this->l('Reverse'),
                        'name' => 'MOD_RELATEDPRODUCTS_REVERSE',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                                    array(
                                        'id' => 'reverse_on',
                                        'value' => true,
                                        'label' => $this->l('Enabled')
                                    ),
                                    array(
                                        'id' => 'reverse_off',
                                        'value' => false,
                                        'label' => $this->l('Disabled')
                                    )
                        ),
                        'desc' => $this->l('A relate to B <-> B relate to A.')
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        //$this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRPPConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $fields = array();

        $fields['MOD_RELATEDPRODUCTS_TYPE'] = Tools::getValue('MOD_RELATEDPRODUCTS_TYPE', Configuration::get('MOD_RELATEDPRODUCTS_TYPE'));
        $fields['MOD_RELATEDPRODUCTS_MAX'] = Tools::getValue('MOD_RELATEDPRODUCTS_MAX', Configuration::get('MOD_RELATEDPRODUCTS_MAX'));
        $fields['MOD_RELATEDPRODUCTS_ISIZE'] = Tools::getValue('MOD_RELATEDPRODUCTS_ISIZE', Configuration::get('MOD_RELATEDPRODUCTS_ISIZE'));
        $fields['MOD_RELATEDPRODUCTS_THEME'] = Tools::getValue('MOD_RELATEDPRODUCTS_THEME', Configuration::get('MOD_RELATEDPRODUCTS_THEME'));
        $fields['MOD_RELATEDPRODUCTS_REVERSE'] = Tools::getValue('MOD_RELATEDPRODUCTS_REVERSE', Configuration::get('MOD_RELATEDPRODUCTS_REVERSE'));
        $fields['MOD_RELATEDPRODUCTS_RANDOMIZE'] = Tools::getValue('MOD_RELATEDPRODUCTS_RANDOMIZE', Configuration::get('MOD_RELATEDPRODUCTS_RANDOMIZE'));

        return $fields;
    }

    public function renderForm2()
    {
        $type_radio = 'radio';
        if (version_compare(_PS_VERSION_, '1.6.0.0 ', '>=')) {
            $type_radio = 'switch';
        }

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Slide Settings'),
                    'icon' => 'icon-cogs'
                ),
                'description' => $this->l('Only available when display style is "Slide".'),
                'input' => array(
                    array(
                        'type' => $type_radio,
                        'label' => $this->l('Pager'),
                        'name' => 'MOD_RELATEDPRODUCTS_SL_PAGER',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                                    array(
                                        'id' => 'pager_on',
                                        'value' => true,
                                        'label' => $this->l('Enabled')
                                    ),
                                    array(
                                        'id' => 'pager_off',
                                        'value' => false,
                                        'label' => $this->l('Disabled')
                                    )
                        ),
                        'desc' => $this->l('If "true", a pager will be added. Default: No')
                    ),
                    array(
                        'type' => $type_radio,
                        'label' => $this->l('Infinite Loop'),
                        'name' => 'MOD_RELATEDPRODUCTS_SL_LOOP',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                                    array(
                                        'id' => 'infinite_on',
                                        'value' => true,
                                        'label' => $this->l('Enabled')
                                    ),
                                    array(
                                        'id' => 'infinite_off',
                                        'value' => false,
                                        'label' => $this->l('Disabled')
                                    )
                        ),
                        'desc' => $this->l('infiniteLoop. If true, clicking "Next" while on the last slide will transition to the first slide. Default: No')
                    ),
                    array(
                        'type' => $type_radio,
                        'label' => $this->l('Auto'),
                        'name' => 'MOD_RELATEDPRODUCTS_SL_AUTO',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                                    array(
                                        'id' => 'auto_on',
                                        'value' => true,
                                        'label' => $this->l('Enabled')
                                    ),
                                    array(
                                        'id' => 'auto_off',
                                        'value' => false,
                                        'label' => $this->l('Disabled')
                                    )
                        ),
                        'desc' => $this->l('Slides will automatically transition. Default: No')
                    ),
                    array(
                        'type' => $type_radio,
                        'label' => $this->l('hide Control On End'),
                        'name' => 'MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                                    array(
                                        'id' => 'control_on',
                                        'value' => true,
                                        'label' => $this->l('Enabled')
                                    ),
                                    array(
                                        'id' => 'control_off',
                                        'value' => false,
                                        'label' => $this->l('Disabled')
                                    )
                        ),
                        'desc' => $this->l('hideControlOnEnd. Default: No')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Image width'),
                        'name' => 'MOD_RELATEDPRODUCTS_SL_WIDTH',
                        'size' => 50,
                        'desc' => $this->l('0 => auto'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Image margin'),
                        'name' => 'MOD_RELATEDPRODUCTS_SL_MARGIN',
                        'size' => 50,
                        'desc' => $this->l('Default: 30'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        //$this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSlideConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues2(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues2()
    {
        $fields = array();

        $fields['MOD_RELATEDPRODUCTS_SL_PAGER'] = Tools::getValue('MOD_RELATEDPRODUCTS_SL_PAGER', Configuration::get('MOD_RELATEDPRODUCTS_SL_PAGER'));
        $fields['MOD_RELATEDPRODUCTS_SL_LOOP'] = Tools::getValue('MOD_RELATEDPRODUCTS_SL_LOOP', Configuration::get('MOD_RELATEDPRODUCTS_SL_LOOP'));
        $fields['MOD_RELATEDPRODUCTS_SL_AUTO'] = Tools::getValue('MOD_RELATEDPRODUCTS_SL_AUTO', Configuration::get('MOD_RELATEDPRODUCTS_SL_AUTO'));
        $fields['MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL'] = Tools::getValue('MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL', Configuration::get('MOD_RELATEDPRODUCTS_SL_AUTO_CONTROL'));
        $fields['MOD_RELATEDPRODUCTS_SL_WIDTH'] = Tools::getValue('MOD_RELATEDPRODUCTS_SL_WIDTH', Configuration::get('MOD_RELATEDPRODUCTS_SL_WIDTH'));
        $fields['MOD_RELATEDPRODUCTS_SL_MARGIN'] = Tools::getValue('MOD_RELATEDPRODUCTS_SL_MARGIN', Configuration::get('MOD_RELATEDPRODUCTS_SL_MARGIN'));

        return $fields;
    }

    public function ajaxProcessExport()
    {
        $model = new RelatedProductModel();
        $data = $model->getAll();

        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=related-product-'.date('Y-d-m_His').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w');

        if (!empty($data)) {
            fputcsv($fp, array_keys($data[0]));
            foreach ($data as $fields) {
                fputcsv($fp, $fields);
            }
        }

        fclose($fp);
        exit();
    }

    public function renderForm3()
    {
        $link_export = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&ajax=true&action=export';

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Import/Export'),
                    'icon' => 'icon-cogs'
                ),
                'description' => '<a href="'.$link_export.'" target=_bank>'.$this->l('Download related products pro data in CSV format').'</a>',
                'input' => array(
                        array(
                        'type' => 'file',
                        'label' => $this->l('Import from CSV file'),
                        'name' => 'inport_file',
                        'size' => 50,
                        'lang' => false
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        //$this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitImportConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => array(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }
}
