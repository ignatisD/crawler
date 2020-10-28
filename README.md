# Crawler - A quick-start helper to website crawling


## Installation

Install the latest version with

```bash
$ composer require iggi/crawler
```

## Basic Usage

```php
<?php
require_once "vendor/autoload.php";

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
/*
array(
    "success" => true,
    "message" => "Hello world!"
)
*/
```

### Author

Ignatios Drakoulas - <ignatisnb@gmail.com> - <https://twitter.com/ignatisd><br />

### License

Crawler is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
