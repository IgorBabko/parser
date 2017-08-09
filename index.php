<?php

require 'vendor/autoload.php';

$parser = new Parser\Parser('http://www.ralphlauren.com');

$parser
    ->scrapeCategories()
    ->scrapeProducts()
    ->scrapeProduct();

$parser->printResult();