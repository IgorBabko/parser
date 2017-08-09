<?php

require 'vendor/autoload.php';

use Sunra\PhpSimple\HtmlDomParser;

// Task 1

$domain = 'http://www.ralphlauren.com';

$dom = HtmlDomParser::file_get_html($domain, false, null, 0);

$categories = [];

foreach($dom->find('.top-nav') as $parentCategoryNode)  {

    $parentCategory['name'] = $parentCategoryNode->find('span')[0]->innertext;
    $parentCategory['link'] = $parentCategoryNode->href;
    $parentCategory['childCategories'] = [];

    $parentCategoryDom = HtmlDomParser::file_get_html($domain . $parentCategoryNode->href, false, null, 0);

    $childCategory['name'] = null;
    foreach ($parentCategoryDom->find('#left-nav .nav > *') as $childCategoryNode) {
        if ($childCategoryNode->class != 'nav-items') {
            $childCategory['name'] = $childCategoryNode->innertext;
        } else {
            $childCategory['subChildCategories'] = [];
            foreach ($childCategoryNode->find('a') as $subcategory) {
                $childCategory['subChildCategories'][] = [
                    'name' => $subcategory->innertext,
                    'link' => $subcategory->href
                ];
            }
            $parentCategory['childCategories'][] = $childCategory;
        }
    }
    $categories[] = $parentCategory;
}

echo '<pre>';
    print_r($categories);
echo '</pre>';


// Task 2


$subChildCategoryLink = $categories[0]['childCategories'][0]['subChildCategories'][0]['link'];

$subChildCategoryCurrentPage = HtmlDomParser::file_get_html($domain . $subChildCategoryLink, false, null, 0);

$totalPages = $subChildCategoryCurrentPage->find('.pagination .total-pages')[0]->innertext;
$pageLink = $subChildCategoryCurrentPage->find('.grid-nav-links .next-page')[0]->href;

$pageLink = substr(preg_replace('/&pg=\d+/', '', $pageLink), 2);

$products = [];

foreach (range(1, $totalPages) as $pageNumber) {

    $subChildCategoryCurrentPage = HtmlDomParser::file_get_html($domain . $pageLink . '&pg=' . $pageNumber, false, null, 0);

    foreach($subChildCategoryCurrentPage->find('li.product') as $productNode)  {

        $product['image'] = $productNode->find('.photo img')[0]->src;
        $product['name'] = $productNode->find('dt .prodtitle')[0]->innertext;
        $product['link'] = $productNode->find('.prodtitle')[0]->href;

        $products[] = $product;
    }
}

// echo '<pre>';
// print_r($products);
// echo '</pre>';




// Task 3

$productLink = $products[4]['link'];

$productPage = HtmlDomParser::file_get_html($domain . $productLink, false, null, 0);

$product = [];

$product['name'] = $productPage->find('.prod-summary .prod-title')[0]->innertext;
$product['price'] = $productPage->find('.prod-summary .prod-price .reg-price')[0]->innertext;
$product['description'] = $productPage->find('#longDescDiv span')[0]->innertext;
$product['brand'] = $productPage->find('.prod-brand-logo img')[0]->alt;

$scripts = $productPage->find('script');

foreach ($scripts as $s) {
    if (strpos($s->innertext, 't940') !== false) {
        $scriptText = $s->innertext;

        preg_match_all("/t940:\s*'(.+?)'/", $scriptText, $imageUrlsMatches);

        $product['images'] = $imageUrlsMatches[1];
    }
}

echo '<pre>';
print_r($product);
echo '</pre>';

