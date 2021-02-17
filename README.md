# MageWorx_SeoMarkupGraphQl

GraphQL API module for Mageworx [Magento 2 SEO Suite Ultimate](https://www.mageworx.com/magento-2-seo-extension.html) extension. 

## Installation
**1) Copy-to-paste method**
- Download this module and upload it to the `app/code/MageWorx/SeoMarkupGraphQl` directory *(create "SeoMarkupGraphQl" first if missing)*

**2) Installation using composer (from packagist)**
- Execute the following command: `composer require mageworx/module-seomarkup-graph-ql`

## How to use

**[SeoMarkupGraphQl](https://github.com/mageworx/MageWorx_SeoMarkupGraphQl)** extends existing Output attributes for Product, Category, CmsPage queries and includes:

1. For Product
<ul>
<li>mw_seo_markup
<ul>
<li>social_markup</li>
<li>rich_snippets</li>
        <ul>
        <li>website</li>
        <li>seller</li>
        <li>product</li>
        </ul>
</li>
</li>
</ul>
</ul>

2. For Category
<ul>
<li>mw_seo_markup
<ul>
<li>social_markup</li>
<li>rich_snippets</li>
        <ul>
        <li>website</li>
        <li>seller</li>
        </ul>
</li>
</li>
</ul>
</ul>

2. For CMS Page
<ul>
<li>mw_seo_markup
<ul>
<li>social_markup</li>
<li>rich_snippets</li>
        <ul>
        <li>website</li>
        <li>seller</li>
        <li>webpage</li>
        </ul>
</li>
</li>
</ul>
</ul>

Other attribute is defined according to the guide: https://devdocs.magento.com/guides/v2.4/graphql/queries/products.html#productfilterinput-attributes.

Product, Category, CmsPage queries have the syntax similar to the Magento user guide.

For example, product query has the following syntax:

```
products(
  search: String
  filter: ProductAttributeFilterInput
  pageSize: Int
  currentPage: Int
  sort: ProductAttributeSortInput
): Products
```

**Request:**

```
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
      }
    }
    }
  }
}
```

**Response:**

```
{
  "data": {
    "products": {
      "items": [
        {
          "name": "Overnight Duffle",
          "sku": "24-WB07",
          "mw_seo_markup": {
            "social_markup": "\n<meta property=\"og:type\" content=\"product.item\"/>\n<meta property=\"og:title\" content=\"Overnight Duffle\"/>\n<meta property=\"og:description\" content=\"\"/>\n<meta property=\"og:url\" content=\"https://store_url/default/overnight-duffle.html\"/>\n<meta property=\"product:price:amount\" content=\"45\"/>\n<meta property=\"product:price:currency\" content=\"USD\"/>\n<meta property=\"og:image\" content=\"https://store_url/media/catalog/product/cache/c52db06da6f0de78fc16c7b26d943b31/w/b/wb07-brown-0.jpg\"/>\n<meta property=\"og:image:width\" content=\"265\"/>\n<meta property=\"og:image:height\" content=\"265\"/>\n<meta property=\"product:availability\" content=\"in stock\"/>\n<meta name=\"twitter:site\" content=\"111222333\"/>\n<meta name=\"twitter:creator\" content=\"111222333\"/>\n<meta name=\"twitter:card\" content=\"summary\"/>\n<meta name=\"twitter:title\" content=\"Overnight Duffle\"/>\n<meta name=\"twitter:description\" content=\"\"/>\n<meta name=\"twitter:image\" content=\"https://store_url/media/catalog/product/cache/c52db06da6f0de78fc16c7b26d943b31/w/b/wb07-brown-0.jpg\"/>\n<meta name=\"twitter:url\" content=\"https://store_url/default/overnight-duffle.html\"/>\n<meta name=\"twitter:label1\" content=\"Price\"/>\n<meta name=\"twitter:data1\" content=\"45\"/>\n<meta name=\"twitter:label2\" content=\"Availability\"/>\n<meta name=\"twitter:data2\" content=\"in stock\"/>\n",
            "rich_snippets": {
              "website": "<script type=\"application/ld+json\">{\"@context\":\"http:\\/\\/schema.org\",\"@type\":\"WebSite\",\"url\":\"https:\\/\\/store_url\\/\"}</script>",
              "seller": "<script type=\"application/ld+json\">{\"@context\":\"http:\\/\\/schema.org\",\"@type\":\"LocalBusiness\",\"name\":\"Name For Seller\",\"description\":\"Description For Seller\",\"address\":{\"@type\":\"PostalAddress\",\"addressLocality\":\"\",\"addressRegion\":\"\",\"streetAddress\":\"Street For Seller\",\"postalCode\":\"\"},\"image\":\"https:\\/\\/store_url\\/media\\/seller_image\\/default\\/best-seller-gold-sign-label-template-vector-1356860.jpg\",\"url\":\"https:\\/\\/store_url\\/\"}</script>",
              "product": "<script type=\"application/ld+json\">{\"@context\":\"http:\\/\\/schema.org\",\"@type\":\"Product\",\"name\":\"Overnight Duffle\",\"description\":null,\"image\":\"https:\\/\\/store_url\\/media\\/catalog\\/product\\/cache\\/c52db06da6f0de78fc16c7b26d943b31\\/w\\/b\\/wb07-brown-0.jpg\",\"offers\":{\"@type\":\"http:\\/\\/schema.org\\/Offer\",\"price\":45,\"url\":\"https:\\/\\/store_url\\/default\\/overnight-duffle.html\",\"priceCurrency\":\"USD\",\"availability\":\"http:\\/\\/schema.org\\/InStock\"},\"aggregateRating\":{\"ratingValue\":\"60\",\"reviewCount\":\"3\",\"bestRating\":100,\"worstRating\":0,\"@type\":\"AggregateRating\"}}</script><script type=\"application/ld+json\">{\"@context\":\"http:\\/\\/schema.org\\/\",\"@type\":\"WebPage\",\"speakable\":{\"@type\":\"SpeakableSpecification\",\"cssSelector\":[\".description\"],\"xpath\":[\"\\/html\\/head\\/title\"]}}</script>"
            }
          }
        }
      ]
    }
```
