<?php

require 'vendor/autoload.php';

$parser = new Parser\Parser('http://www.ralphlauren.com');

$parser
    ->scrapeCategories()
    ->scrapeProducts()
    ->scrapeProducts();

$parser->printResult();
