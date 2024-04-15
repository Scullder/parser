<?php

ini_set('max_execution_time', 1800);

require_once 'vendor/autoload.php';
require_once 'config.php';

use Src\Parser;
use Src\Writers\WriterCSV;
use React\Http\Browser;

$client = new Browser;
$parser = new Parser($client);

// проходим по всем страницам, собирая массив ссылок на авто
$catalogUrls = $parser->getCatalogUrls(CATALOG_URL, MAX_PAGES);

// проходим по собранным ссылкам, формируя массив для записи в файл
$catalogItems = $parser->parse($catalogUrls);

// записываем данные в CSV файл
$writer = new WriterCSV;
try {
    $writer->write('catalog.csv', $catalogItems);
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

echo 'Parsing complete!' . PHP_EOL;
