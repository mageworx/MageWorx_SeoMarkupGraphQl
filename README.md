# MageWorx_SeoMarkupGraphQl

GraphQL API module for MageWorx [Magento 2 SEO Suite Ultimate](https://www.mageworx.com/magento-2-seo-extension.html).

## Installation

1. **Copy-to-paste method**

   Download this module and upload it to the `app/code/MageWorx/SeoMarkupGraphQl` directory. Create the `SeoMarkupGraphQl` directory first if it does not exist.

2. **Installation using Composer (from Packagist)**

   ```bash
   composer require mageworx/module-seomarkup-graph-ql
   ```

## How to use

[SeoMarkupGraphQl](https://github.com/mageworx/MageWorx_SeoMarkupGraphQl) extends the output attributes of the Product, Category and CMS Page queries with the `mw_seo_markup` field.

### Supported fields

- **Product**
  - `mw_seo_markup`
    - `social_markup`
    - `rich_snippets`
      - `website`
      - `seller`
      - `product`
      - `breadcrumbs`
- **Category**
  - `mw_seo_markup`
    - `social_markup`
    - `rich_snippets`
      - `website`
      - `seller`
      - `breadcrumbs`
- **CMS Page**
  - `mw_seo_markup`
    - `social_markup`
    - `rich_snippets`
      - `website`
      - `seller`
      - `webpage`
      - `breadcrumbs`

Other attributes are defined according to the [Magento GraphQL guide](https://devdocs.magento.com/guides/v2.4/graphql/queries/products.html#productfilterinput-attributes).

Product, Category and CMS Page queries use syntax similar to the Magento user guide.

### Product query signature

```graphql
products(
  search: String
  filter: ProductAttributeFilterInput
  pageSize: Int
  currentPage: Int
  sort: ProductAttributeSortInput
): Products
```

### Request

```graphql
{
  products(filter: {sku: {eq: "24-WB04"}}) {
    items {
      name
      sku
      mw_seo_markup {
        social_markup
        rich_snippets {
          website
          seller
          product
          breadcrumbs
        }
      }
    }
  }
}
```

### Response

```json
{
  "data": {
    "products": {
      "items": [
        {
          "name": "Overnight Duffle",
          "sku": "24-WB07",
          "mw_seo_markup": {
            "social_markup": "\\n<meta property=\\\"og:type\\\" content=\\\"product.item\\\"/>\\n<meta property=\\\"og:title\\\" content=\\\"Overnight Duffle\\\"/>\\n...",
            "rich_snippets": {
              "website": "<script type=\\\"application/ld+json\\\">{\\\"@context\\\":\\\"http://schema.org\\\",\\\"@type\\\":\\\"WebSite\\\",\\\"url\\\":\\\"https://store_url/\\\"}</script>",
              "seller": "<script type=\\\"application/ld+json\\\">{...}</script>",
              "product": "<script type=\\\"application/ld+json\\\">{...}</script>",
              "breadcrumbs": "<script type=\\\"application/ld+json\\\">{...}</script>"
            }
          }
        }
      ]
    }
  }
}
```

## Known limitations

The `breadcrumbs` rich snippet rebuilds the category path directly from the current category/product so it stays correct across items in a list query. As a result, third-party plugins on `Magento\Catalog\Helper\Data::getBreadcrumbPath()` are not reflected in the GraphQL output, unlike on the storefront.
