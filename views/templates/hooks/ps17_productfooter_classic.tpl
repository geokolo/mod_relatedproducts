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
<section class="page-product-box" style="margin-top:25px;">
	<h3 class="page-product-heading">{l s='Related Products' mod='mod-relatedproducts'}</h3>

    <div id="mod-related-product" class="block_content mod-related-product">
		<ul class="products-block">
            {assign var=i value=0}
			{foreach from=$products item="product"}
				<li class="item-products-block{if (($i % 2) == 0)} even-products-block{/if}" data-id-product="{$product.id_product|escape:'htmlall':'UTF-8'}" data-id-product-attribute="{$product.id_product_attribute|escape:'htmlall':'UTF-8'}" itemscope itemtype="http://schema.org/Product">
					{block name='product_thumbnail'}
					  <a href="{$product.url}" class="thumbnail product-thumbnail products-block-image">
						<img
						  src = "{$product.cover.bySize.small_default.url}"
						  alt = "{$product.cover.legend|escape:'htmlall':'UTF-8'}"
						  data-full-size-image-url = "{$product.cover.large.url}"
						>
					  </a>
					{/block}
					<div class="product-content product-container">
						<h5 itemprop="name">
							<a class="product-name" href="{$product.url}" title="{$product.name|escape:'htmlall':'UTF-8'}">{$product.name|truncate:30:'...'|escape:'htmlall':'UTF-8'}</a>
						</h5>
                       	<p class="product-description">{$product.description_short|strip_tags:'UTF-8'|escape:'htmlall':'UTF-8'|truncate:95:'...'}</p>
						
					  {block name='product_price_and_shipping'}
						{if $product.show_price}
						  <div class="product-price-and-shipping">
							{if $product.has_discount}
							  {hook h='displayProductPriceBlock' product=$product type="old_price"}

							  <span class="regular-price">{$product.regular_price|escape:'htmlall':'UTF-8'}</span>
							  {if $product.discount_type === 'percentage'}
								<span class="discount-percentage">{$product.discount_percentage|escape:'htmlall':'UTF-8'}</span>
							  {/if}
							{/if}

							{hook h='displayProductPriceBlock' product=$product type="before_price"}

							<span itemprop="price" class="price">{$product.price|escape:'htmlall':'UTF-8'}</span>

							{hook h='displayProductPriceBlock' product=$product type='unit_price'}

							{hook h='displayProductPriceBlock' product=$product type='weight'}
						  </div>
						{/if}
					  {/block}
					</div>
				</li>
                {assign var=i value=$i+1}
			{/foreach}
		</ul>
	</div>

</section>
<!--end Related Products -->