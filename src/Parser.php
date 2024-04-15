<?php

namespace Src;

use React\Http\Browser;
use function React\Async\await;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{
    private const string CONDITION = 'Used';
    private const int GOOGLE_PRODUCT_CATEGORY = 123;
    private const string STORE_CODE = 'xpremium';
    private const string VEHICLE_FULFILLMENT = 'in_store:premium';
    private array $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
    ];

    public function __construct(
        private Browser $client,
    ) {}

    public function getCatalogUrls(string $firstPageUrl, int $maxPages): array
    {
        $page = 1;
        $catalogUrls = [];

        while ($page <= $maxPages) {
            $promise = $this->client->get($firstPageUrl . 'page/' . $page, $this->headers);

            try {
                $response = await($promise);
            } catch (\Exception $e) {
                echo 'An error occurred while performing the request!' . PHP_EOL;
                echo $e->getMessage() . PHP_EOL;
                continue;
            }

            $crawler = new Crawler($response->getBody());
            $items = $crawler->filter('main .main-items-wrapper .items-wrapper .row article .right-inner .listing-title a');

            if (!count($items)) {
                break;
            }

            foreach ($items as $item) {
                $catalogUrls[] = (new Crawler($item))->attr('href');
            }

            $page++;
        }

        return $catalogUrls;
    }

    public function parse(array $urls): array
    {
        $items = [
            [
                'Condition',
                'google_product_category',
                'store_code',
                'vehicle_fulfillment(option:store_code)',
                'Brand',
                'Model',
                'Year',
                'Color',
                'Mileage',
                'Price',
                'VIN',
                'image_link',
                'link_template',
            ],
        ];

        foreach ($urls as $url) {
            $promise = $this->client->get($url, $this->headers);

            try {
                $response = await($promise);
            } catch (\Exception $e) {
                echo 'An error occurred while performing the request to "' . $url . '"!' . PHP_EOL;
                echo $e->getMessage() . PHP_EOL;
                continue;
            }

            $item = $this->getItemFromHTML($response->getBody(), $url);

            if (!empty($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function getItemFromHTML(string $html, string $url): array
    {
        $item = [];

        $crawler = new Crawler($html);

        $details = [];
        $detailsUL = $crawler->filter('.sidebar .listing-detail-detail ul.list li');

        foreach ($detailsUL as $li) {
            $liCrawler = new Crawler($li);

            $key = $liCrawler->filter('div.text');
            $value = $liCrawler->filter('div.value');

            if ($key->count() && $value->count()) {
                $details[strtolower(trim($key->innerText(), ':'))] = $value->text();
            }
        }

        $images = $crawler->filter('.listing-detail-gallery .right-images a.p-popup-image');
        $price = $crawler->filter('.listing-detail-header .listing-price span.price-text');

        $item = [
            self::CONDITION,
            self::GOOGLE_PRODUCT_CATEGORY,
            self::STORE_CODE,
            self::VEHICLE_FULFILLMENT,
            $details['make'] ?? '',
            $details['model'] ?? '',
            $details['year'] ?? '',
            $details['color'] ?? '',
            !empty($details['mileage']) ? $details['mileage'] . ' miles' : '',
            $price->count() ? intval(str_replace(',', '', $price->text())) : 0,
            $details['vin'] ?? '',
            $images->count() > 2 ? $images->eq(1)->attr('href') : '',
            $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'store=' . self::VEHICLE_FULFILLMENT,
        ];

        return $item;
    }
}
