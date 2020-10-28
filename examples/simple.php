<?php
require_once dirname(__DIR__)."/vendor/autoload.php";

use Iggi\Crawler;

class MyCrawler extends Crawler {

    protected $uri = "https://ignatisd.gr";

    public function __construct($proxy = null, $debug = 0)
    {
        parent::__construct($proxy, $debug);
    }

    public function hello() {
        $response = $this->curlRequest->get($this->getUrl("/hello"))->exec();
        if ($response->code === 200) {
            return json_decode($response->body, true);
        }
        return $this->errorHandler("Request failed");
    }
}


$crawler = new MyCrawler();
$result = $crawler->hello();
print_r($result);
