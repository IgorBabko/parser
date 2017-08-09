<?php

namespace Parser;

use Sunra\PhpSimple\HtmlDomParser;

class Parser
{
    protected $subChildCategoryLink;

    protected $productLink;

    protected $categories;

    protected $products;

    protected $product;

    protected $domain;

    public function __construct($domain = 'http://www.ralphlauren.com')
    {
        $this->domain = $domain;
    }

    public function printResult()
    {
        echo '<h1>Categories</h1>';
        dump($this->categories);

        echo '<h1>Products</h1>';
        echo "<p><a href='{$this->subChildCategoryLink}'>Category link</a></p>";
        dump($this->products);

        echo '<h1>Product</h1>';
        echo "<h4><a href='{$this->productLink}'>Product link</a></h4>";
        dump($this->product);
    }

    public function scrapeCategories()
    {
        $dom = $this->getHtml($this->domain);

        foreach($dom->find('.top-nav') as $parentCategoryNode) {

            $parentCategory['name'] = $parentCategoryNode->find('span')[0]->innertext;
            $parentCategory['link'] = $parentCategoryNode->href;
            $parentCategory['childCategories'] = [];

            $parentCategoryDom = $this->getHtml($this->domain . $parentCategoryNode->href);

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
            $this->categories[] = $parentCategory;
        }

        return $this;
    }

    public function scrapeProducts($parentCategoryId = 1, $childCategoryId = 1, $subChildCategoryId = 1)
    {
        $this->subChildCategoryLink = $this->domain . $this->getSubchildCategoryLink($parentCategoryId, $childCategoryId, $subChildCategoryId);

        $subChildCategoryPage = $this->getHtml($this->subChildCategoryLink);

        $this->products = $this->buildProducts($subChildCategoryPage);

        return $this;
    }

    public function scrapeProduct($productId = 1)
    {
        $this->productLink = $this->domain . $this->products[$productId]['link'];

        $productPage = $this->getHtml($this->productLink);

        $this->product = $this->buildExpandedProduct($productPage); 

        return $this;
    }

    protected function buildProducts($dom)
    {
        $products = [];

        $totalPages = $dom->find('.pagination .total-pages')[0]->innertext;
        $pageLink = $dom->find('.grid-nav-links .next-page')[0]->href;

        $pageLink = substr(preg_replace('/&pg=\d+/', '', $pageLink), 2);

        foreach (range(1, $totalPages) as $pageNumber) {
            $subChildCategoryCurrentPage = $this->getHtml($this->domain . $pageLink . '&pg=' . $pageNumber);

            foreach($subChildCategoryCurrentPage->find('li.product') as $productNode)  {
                $products[] = $this->buildProduct($productNode);
            }
        }

        return $products;
    }

    protected function getHtml($url)
    {
        return HtmlDomParser::file_get_html($url, false, null, 0);
    }

    protected function buildProduct($productNode)
    {
        $product['image'] = $productNode->find('.photo img')[0]->src;
        $product['name'] = $productNode->find('dt .prodtitle')[0]->innertext;
        $product['link'] = $productNode->find('.prodtitle')[0]->href;

        return $product;
    }

    protected function buildExpandedProduct($dom)
    {
        return [
            'name' => $dom->find('.prod-summary .prod-title')[0]->innertext,
            'price' => $dom->find('.prod-summary .prod-price .reg-price')[0]->innertext,
            'description' => $dom->find('#longDescDiv span')[0]->innertext,
            'brand' => $dom->find('.prod-brand-logo img')[0]->alt,
            'images' => $this->getImageUrls($dom)
        ];
    }

    protected function getImageUrls($productPage)
    {
        $scripts = $productPage->find('script');

        foreach ($scripts as $s) {
            if (strpos($s->innertext, 't940') !== false) {
                $scriptText = $s->innertext;

                preg_match_all("/t940:\s*'(.+?)'/", $scriptText, $imageUrlsMatches);

                return $imageUrlsMatches[1];
            }
        }
    }

    protected function getSubchildCategoryLink($parentCategoryId, $childCategoryId, $subchildCategoryId)
    {
        return $this->categories[$parentCategoryId]['childCategories'][$childCategoryId]['subChildCategories'][$subchildCategoryId]['link'];
    }
}