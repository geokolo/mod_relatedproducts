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

{if (count($products))}
<!-- Related Products -->
<div class="col-md-12 mb-2 mt-2 hidden-sm-down">
<section id="products" class="mod-related-product page-product-box">
	<h2 align="center" class="text-success">{l s='Related Products' mod='mod-relatedproducts'}</h2>

    <div id="mod-related-product" class="block_content mod-related-product">
		<div class="products product_list row">
		{foreach from=$products item="product"}
		  {block name='product_miniature'}
			{include file='catalog/_partials/miniatures/product.tpl' product=$product}
		  {/block}
		{/foreach}
		</div>
	</div>

</section>
</div>

<script type="text/javascript">
	var slide_pager = {if (isset($slide_pager) && $slide_pager)}true{else}false{/if},
	slide_infiniteLoop = {if (isset($slide_infiniteLoop) && $slide_infiniteLoop)}true{else}false{/if},
	slide_auto = {if (isset($slide_auto) && $slide_auto)}true{else}false{/if},
	slide_hideControlOnEnd = {if (isset($slide_hideControlOnEnd) && $slide_hideControlOnEnd)}true{else}false{/if},
	slide_slideWidth = {if (isset($slide_slideWidth) && $slide_slideWidth)}{$slide_slideWidth|escape:'htmlall':'UTF-8'}{else}{$homeSize.width|escape:'htmlall':'UTF-8'} + 20{/if},
	slide_slideMargin = {if (isset($slide_slideMargin) && $slide_slideMargin != null)}{$slide_slideMargin|escape:'htmlall':'UTF-8'}{else}30{/if};
</script>
<!--end Related Products -->
{/if}