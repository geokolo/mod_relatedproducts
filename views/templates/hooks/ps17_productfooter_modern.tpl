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

<!-- Related Products -->
{if (count($products))}
<section id="products" class="page-product-box">
	<h3 class="page-product-heading">{l s='Related Products' mod='mod-relatedproducts'}</h3>
	<div class="mainList">
		<div  class="products row">
		{foreach from=$products item="product"}
		  {block name='product_miniature'}
			{include file='catalog/_partials/miniatures/product.tpl' product=$product}
		  {/block}
		{/foreach}
		</div>
	</div>
</section>
{/if}
<!--end Related Products -->