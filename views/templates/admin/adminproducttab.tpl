{**
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
*}

{if isset($id_product)}
<div id="product_related" class="panel product-tab" ajax-remote_url="{$remote_url|escape:'html':'UTF-8'}" ajax-link="{$module_ajax_url|escape:'html':'UTF-8'}" id-product="{$id_product|escape:'html':'UTF-8'}">
    <input type="hidden" name="submitted_tabs[]" value="Related Product Pro" />
	<input type="hidden" name="id_shop" value="{$id_shop|escape:'htmlall':'UTF-8'}" />
	<div class="panel-heading tab" >
		<i class="icon-link"></i> {l s='Related Products' mod='mod-relatedproducts'} {l s='(autosave)' mod='mod-relatedproducts'}
		<span class="link_setting" ><a href="{$link_to_setting|escape:'htmlall':'UTF-8'}">{l s='Settings' mod='mod-relatedproducts'}</a></span>
	</div>
	<div class="content">
	<br/>
		<fieldset class="form-group">
			<label class="control-label col-lg-3" for="product_autocomplete_input">
				<span class="" >
					{l s='Search a product' mod='mod-relatedproducts'}
				</span>
			</label>
			<div class="col-lg-6">
					<div class="input-group">
						<input type="text" id="product_autocomplete_input" name="product_autocomplete_input" class="form-control" />
						<span class="input-group-addon"><i class="icon icon-search"></i></span>
					</div>
					<div class="ajax_result" style="display:none"></div>
			</div>
		</fieldset>
	</div>

    <table class="table table-striped related_product" cellpadding="0" cellspacing="0" style="width: 100%; margin-bottom:10px;">
		<thead>
			<tr class="nodrag nodrop" style="height: 40px">
				<th class="center">
				</th>
				<th class="left">
					<span class="title_box">
						{l s='Product ID' mod='mod-relatedproducts'}
					</span>
				</th>
				<th class="left">
					<span class="title_box">
						{l s='Product Name' mod='mod-relatedproducts'}
					</span>
				</th>
				<th class="right">{l s='Actions' mod='mod-relatedproducts'}</th>
			</tr>
		</thead>
		<tbody>
            <tr id="row_template" class="alt_row row_hover">
                <td class="center">
                    <i class="icon icon-arrows-v"></i>
                </td>
                <td class="left">
                </td>
                <td class="left name">

                </td>
                <td class="right">
                    <input type="hidden" name="related_product_input[]" value="" />
                    <a href="javascript:void(0)" class="delete_product_related pull-right btn btn-default">
                        {if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>')}
                        <i class="icon-trash"></i> {l s='Delete this product' mod='mod-relatedproducts'}
                        {else}
                        <img src="../img/admin/disabled.gif">
                        {/if}
                    </a>
                </td>
            </tr>
            {foreach from=$related_products item=product}
				<tr class="alt_row row_hover{if !$product.active} disabled{/if}">
					<td class="center">
                        <i class="icon icon-arrows-v"></i>
					</td>
					<td class="left">
						{$product.id_product|escape:'htmlall':'UTF-8'}
					</td>
					<td class="left name">
                        {$product.name|escape:'htmlall':'UTF-8'}
						({l s='ref' mod='mod-relatedproducts'}: {$product.reference|escape:'htmlall':'UTF-8'})
						{if !$product.active}<span class="label color_field" style="background-color:#8f0621;color:white;">{l s='Disabled' mod='mod-relatedproducts'}</span>{/if}
					</td>
					<td class="right">
                        <input type="hidden" name="related_product_input[]" value="{$product.id_product|escape:'htmlall':'UTF-8'}" />
                        <a href="javascript:void(0)" class="delete_product_related pull-right btn btn-default">
                            {if version_compare($smarty.const._PS_VERSION_, '1.6.0.0', '>')}
                            <i class="icon-trash"></i> {l s='Delete this product' mod='mod-relatedproducts'}
                            {else}
                            <img src="../img/admin/disabled.gif">
                            {/if}
						</a>
					</td>
				</tr>
			{foreachelse}
				<tr>
					<td colspan="6" class="center">
						<b>{l s='No products' mod='mod-relatedproducts'}</b>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
    <p class="text-right">{l s='(Drag & Drop to re-arrange the products)' mod='mod-relatedproducts'}</p>
</div>
{/if}

<script type="text/javascript">
var confirm_delete_related = "{l s='Are you sure to delete this item?' mod='mod-relatedproducts' js=1}";
{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '<')}
$(document).ready(function() {
    $('#product_related').skRelatedProduct();
});
{/if}
</script>