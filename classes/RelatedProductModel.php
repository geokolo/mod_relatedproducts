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

class RelatedProductModel extends ObjectModel
{
    public $id_product;
    public $id_related;
    public $id_shop;
    public $position = 0;

    public static $definition = array(
        'table' => 'mod_relatedproducts',
        'primary' => array('id_product', 'id_related', 'id_shop'),
        'multilang' => false,
        'fields' => array(
            'id_product'  => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'id_related'  => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'id_shop'     => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'position'    => array('type' => self::TYPE_INT, 'validate' => 'isInt')
        ),
    );

    public function __construct($id_product = 0, $id_shop = 1)
    {
        if ($id_product) {
            $this->id_product = (int)$id_product;
        }
        $this->id_shop = (int)$id_shop;
    }

    public function getRelatedProducts($reverse = false, $relation = 0, $is_admin = false)
    {
        $id_lang = (int)Context::getContext()->language->id;
        $id_shop = (int)Context::getContext()->shop->id;

        if ($relation == 3) {
            return Db::getInstance()->executeS('
                    SELECT DISTINCT `id_product`
                        FROM `'._DB_PREFIX_.'category_product` WHERE `id_product`!= '.(int)$this->id_product.'
                        AND `id_category` IN (
                                SELECT `id_category`
                                FROM `'._DB_PREFIX_.'category_product`
                                WHERE `id_product` = '.(int)$this->id_product.' ORDER BY `position` ASC
                            )
                        AND `id_product` IN (SELECT `id_product` FROM `'._DB_PREFIX_.'product_shop` WHERE `active`=1
                        )');
        } elseif ($relation == 2) {
            return Db::getInstance()->executeS('
                    SELECT DISTINCT `id_product`
                        FROM `'._DB_PREFIX_.'category_product` WHERE `id_product`!= '.(int)$this->id_product.'
                        AND `id_category` IN (
                            SELECT `id_category_default`
                            FROM `'._DB_PREFIX_.'product`
                            WHERE `id_product` = '.(int)$this->id_product.'
                        )');
        } elseif ($relation == 1) {
            return Db::getInstance()->executeS('
                    SELECT DISTINCT `id_product`
                        FROM `'._DB_PREFIX_.'product_tag` WHERE `id_product`!= '.(int)$this->id_product.'
                        AND `id_tag` IN (
                            SELECT `id_tag`
                            FROM `'._DB_PREFIX_.'product_tag`
                            WHERE `id_product` = '.(int)$this->id_product.'
                        )');
        } elseif ($relation == 4) {
            return Db::getInstance()->executeS('
                    SELECT DISTINCT `id_product`
                        FROM `'._DB_PREFIX_.'product_tag` WHERE `id_product`!= '.(int)$this->id_product.'
                        AND `id_tag` IN (
                            SELECT pt.`id_tag`
                            FROM `'._DB_PREFIX_.'product_tag` pt
                            LEFT JOIN `'._DB_PREFIX_.'tag` t ON (pt.id_tag = t.id_tag AND pt.id_lang = t.id_lang)
                            WHERE pt.`id_product` = '.(int)$this->id_product.' AND t.name NOT LIKE \'&%\'
                        )');
        } elseif ($relation == 5) {
            return Db::getInstance()->executeS('
                    SELECT DISTINCT `id_product`
                        FROM `'._DB_PREFIX_.'category_product` WHERE `id_product`!= '.(int)$this->id_product.' 
                        AND `id_product` IN (SELECT `id_product` FROM `'._DB_PREFIX_.'product_shop` WHERE `active`=1
                        )');
        } else {
            $query1 = Db::getInstance()->executeS('SELECT DISTINCT prp.`id_related` as `id_product`, p.`reference`, pl.`name`, p.`active`
                    FROM `'._DB_PREFIX_.'mod_relatedproducts` prp
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (prp.id_related = p.id_product)
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                        ON (
                            pl.id_product = p.id_product AND pl.`id_lang` = '.(int)$id_lang.'
                            AND pl.`id_shop` = '.(int)$id_shop.'
                        )
                    WHERE '.
                        ((!$is_admin) ? 'p.`active` = 1 AND ' : '').
                    'prp.`id_shop` = '.(int)$this->id_shop.'
                    AND prp.`id_product` = '.(int)$this->id_product.'
                    ORDER BY prp.`position` ASC');

            $query2 = Db::getInstance()->executeS('SELECT DISTINCT prp.`id_product`, p.`reference`, pl.`name`, p.`active`
                    FROM `'._DB_PREFIX_.'mod_relatedproducts` prp
                    LEFT JOIN `'._DB_PREFIX_.'product` p ON (prp.`id_product` = p.`id_product`)
                    LEFT JOIN
                        `'._DB_PREFIX_.'product_lang` pl
                    ON (
                        pl.id_product = p.id_product AND pl.`id_lang` = '.(int)$id_lang.'
                        AND pl.`id_shop` = '.(int)$id_shop.'
                    )
                    WHERE '.
                        ((!$is_admin) ? 'p.`active` = 1 AND ' : '').
                    'prp.`id_shop` = '.(int)$this->id_shop.'
                    AND prp.`id_related` = '.(int)$this->id_product.'
                    AND prp.`id_product` NOT IN (
                        SELECT
                            `id_related`
                        FROM
                            `'._DB_PREFIX_.'mod_relatedproducts`
                        WHERE `id_product` = '.(int)$this->id_product.'
                    )
                    ORDER BY prp.`position` ASC');

            if ($reverse) {
                return array_filter(array_merge($query1, $query2));
            } else {
                return $query1;
            }
        }
    }

    public function isExist()
    {
        $sql = 'SELECT COUNT(*) FROM `'._DB_PREFIX_.'mod_relatedproducts` WHERE
        id_product = '.(int)$this->id_product.' AND id_related = '.(int)$this->id_related.' AND id_shop = '.(int)$this->id_shop.';';

        $query = Db::getInstance()->getValue($sql);
        return $query;
    }

    public function relatedUpdate($related_product)
    {
        $this->deleteByProduct();
        if (empty($related_product)) {
            return false;
        }
        
        foreach ($related_product as $key => $id_related) {
            if ($id_related) {
                $model = new RelatedProductModel($this->id_product, $this->id_shop);
                $model->id_related = $id_related;
                $model->position = $key;
                if ($model->isExist()) {
                    $model->updRelatedProductPosition();
                } else {
                    $model->addRelatedProduct();
                }
            }
        }
    }

    public function getHighestPosition()
    {
        $sql = 'SELECT MAX(`position`) FROM `'._DB_PREFIX_.'mod_relatedproducts`
            WHERE `id_shop` = '.(int)$this->id_shop.'
                AND (`id_product` = '.(int)$this->id_product.' OR `id_related` = '.(int)$this->id_product.')';
        $query = Db::getInstance()->getValue($sql);
        if (empty($query)) {
            return 0;
        }
        return $query;
    }
    
    public function addRelatedProduct()
    {
        $sql = 'INSERT INTO `'._DB_PREFIX_.'mod_relatedproducts` (id_product, id_related, id_shop, position)';
        $sql .= ' VALUES (';
        $sql .= (int)$this->id_product.',';
        $sql .= (int)$this->id_related.',';
        $sql .= (int)$this->id_shop.',';
        $sql .= (int)$this->position.'';
        $sql .= ');';

        return Db::getInstance()->execute($sql);
    }

    public function updRelatedProductPosition()
    {
        $sql = 'UPDATE `'._DB_PREFIX_.'mod_relatedproducts` SET position = '.(int)$this->position.'
        WHERE id_product = '.(int)$this->id_product.' AND id_related = '.(int)$this->id_related.' AND id_shop = '.(int)$this->id_shop.';';
        return Db::getInstance()->execute($sql);
    }

    public function updatePosition()
    {
        $sql = 'UPDATE `'._DB_PREFIX_.'mod_relatedproducts` SET position = '.(int)$this->position.'
            WHERE ((id_product = '.(int)$this->id_product.' AND id_related = '.(int)$this->id_related.')';

        if ((bool)Configuration::get('MOD_RELATEDPRODUCTS_REVERSE')) {
            $sql .= ' OR (`id_product` = '.(int)$this->id_related.' AND `id_related` = '.(int)$this->id_product.')';
        }
        $sql .= ') AND id_shop = '.(int)$this->id_shop.';';
        return Db::getInstance()->execute($sql);
    }

    public function deleteOne()
    {
        $sql = 'DELETE FROM `'._DB_PREFIX_.'mod_relatedproducts` WHERE';
        if (!Configuration::get('MOD_RELATEDPRODUCTS_REVERSE')) {
            $sql .= ' (`id_product` = '.(int)$this->id_product.' AND `id_related` = '.(int)$this->id_related.')';
        } else {
            $sql .= ' (`id_product` = '.(int)$this->id_related.' OR `id_related` = '.(int)$this->id_related.')';
        }
        $sql .= ' AND `id_shop` = '.(int)$this->id_shop;

        return Db::getInstance()->execute($sql);
    }

    public function deleteByProduct()
    {
        $sql = 'DELETE FROM `'._DB_PREFIX_.'mod_relatedproducts`
            WHERE (`id_product` = '.(int)$this->id_product;
        if ((bool)Configuration::get('MOD_RELATEDPRODUCTS_REVERSE')) {
            $sql .= ' OR `id_related` = '.(int)$this->id_product;
        }
        $sql .= ') AND `id_shop` = '.(int)$this->id_shop;

        return Db::getInstance()->execute($sql);
    }

    public function getProductData($id_product)
    {
        $sql = 'SELECT product_shop.id_product, product_attribute_shop.id_product_attribute
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN  `'._DB_PREFIX_.'product_attribute` pa ON (product_shop.id_product = pa.id_product)
                '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.default_on = 1').'
                WHERE product_shop.`active` = 1 
                AND p.`active` = 1 
                AND product_shop.`id_product`='.(int)$id_product.' 
                AND p.`id_product`='.(int)$id_product.' 
                AND product_shop.`visibility` IN ("both", "catalog") 
                AND p.`visibility` IN ("both", "catalog") 
                AND (pa.id_product_attribute IS NULL OR product_attribute_shop.default_on = 1)
                GROUP BY product_shop.id_product';

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        $sql = 'SELECT p.*, product_shop.*, stock.`out_of_stock` out_of_stock, pl.`description`, pl.`description_short`,
                    pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`,
                    p.`ean13`, p.`upc`, image_shop.`id_image`, il.`legend`, t.`rate`, m.`name` AS manufacturer_name
                FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
                    p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = '.(int)Context::getContext()->cookie->id_lang.Shop::addSqlRestrictionOnLang('pl').'
                )
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)'.
                Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
                LEFT JOIN `'._DB_PREFIX_.'image_lang` il
                    ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)Context::getContext()->cookie->id_lang.')
                LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group`
                    AND tr.`id_country` = '.(int)Context::getContext()->country->id.'
                    AND tr.`id_state` = 0)
                LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = tr.`id_tax`)
                '.Product::sqlStock('p', 0).'
                LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                WHERE p.id_product = '.(int)$id_product.'
                AND p.`active` = 1 
                AND p.`visibility` IN ("both", "catalog") 
                AND (i.id_image IS NULL OR image_shop.id_shop='.(int)Context::getContext()->shop->id.')';

        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        if (!$row) {
            return false;
        }

        if ($result['id_product_attribute']) {
            $row['id_product_attribute'] = $result['id_product_attribute'];
        }

        $product = Product::getProductProperties(Context::getContext()->cookie->id_lang, $row);

        if ($product['reduction']) {
            // Price without TAX
            $product['price_without_reduction_without_tax'] = Tools::displayPrice($product['price_tax_exc'] + $product['reduction']);
            // Price with tax included
            $product['price_without_reduction'] = Tools::displayPrice(
                Product::getPriceStatic(
                    $product['id_product'],
                    true,
                    $product['id_product_attribute'],
                    6,
                    null,
                    false,
                    false
                )
            );
        }

        $product['quantity'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']);

        return $product;
    }
    
    public function getAll()
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mod_relatedproducts`';
        return Db::getInstance()->executeS($sql);
    }
}
