<?php
/**
 * Created by PhpStorm.
 * User: iggi
 * Date: 20/8/2018
 * Time: 1:01 μμ
 */

namespace Iggi;

/**
 * Class Crawler
 *
 * This is the main class from which all other *Crawler type class will derive
 */
class Crawler
{
    protected $curlRequest = null;
    protected $proxy = null;
    protected $debug = 0;
    protected $started_at = 0;
    protected $uri = "";

    /**
     * The Crawler constructor.
     * @param null|string $proxy
     * @param int $debug
     */
    protected function __construct($proxy = null, $debug = 0)
    {
        $this->started_at = microtime(true);
        $this->debug = $debug;
        $this->proxy = $proxy;
        $this->curlRequest = new CurlRequest($proxy);
        if ($debug > 1) {
            $this->curlRequest->setDebug(true);
        }
    }

    protected function getUrl($path = "/", $queryParams = array()) {
        $url = $this->uri . $path . "?";
        if (!empty($queryParams)) {
            $url .= http_build_query($queryParams);
        }
        return $url;
    }

    /**
     * Set the Crawler base URI
     * @param string $uri
     * @return Crawler
     */
    public function setUri($uri = "") {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Setting the debug level
     * @param $level
     * @return Crawler
     */
    public function setDebug($level = 0)
    {
        if(!empty($level) && is_numeric($level) && $level > 0){
            $this->debug = $level;
        }else{
            $this->debug = 0;
        }
        return $this;
    }

    /**
     * Set the proxy
     * @param $proxy
     * @return Crawler
     */
    public function setProxy($proxy = null)
    {
        $this->proxy = $proxy;
        $this->curlRequest->setProxy($proxy);
        return $this;
    }

    /**
     * Check for empty login params
     * @return bool
     */
    public function checkLogin() {
        $result = true;
        $arg_list = func_get_args();
        foreach($arg_list as $arg) {
            if (empty($arg)) {
                return false;
            }
        }
        return $result;
    }

    /**
     * Returns timing information and if debug is enabled prints any extra variables supplied.
     * @param $debugData
     * @return array
     */
    public function getParams($debugData = null)
    {
        $data = array();
        $data["timing"] = sprintf("%01.2f sec",(microtime(true) - $this->started_at));
        if($this->debug){
            $data["requests"] = $this->curlRequest->getResponses($this->debug < 2);
            if(!empty($debugData)){
                $data["debug"] = $debugData;
            }
        }

        return $data;
    }

    /**
     * The error handler
     * @param $reason
     * @param null $details
     * @return mixed
     */
    public function errorHandler($reason, $details = null)
    {
        $data["success"] = false;
        $data["reason"] = $reason;
        $data["details"] = $this->getParams($details);
        return $data;
    }

    /**
     * Replaces consecutive spaces with just one and also replaces html entities space, euro and br with space nothing and -
     * @param $str
     * @param $trim
     * @param $replacements
     * @return mixed
     */
    public static function replaceStr($str, $trim = true, $replacements = null)
    {
        $map = array(
            "&nbsp;" => " ",
            "&euro;" => "",
            "<br />" => "- "
        );
        if (!empty($replacements)) {
            foreach ($replacements as $key => $value) {
                $map[$key] = $value;
            }
        }
        $result = preg_replace('!\s+!', ' ', str_replace(array_keys($map), array_values($map),$str));
        if ($trim) {
            $result = trim($result);
        }
        return $result;
    }

    /**
     * Clears a given string of any non numbers characters
     * @param $str
     * @return mixed
     */
    public static function onlyFloat($str)
    {
        return preg_replace('/[^0-9\,\.]/', '', $str);
    }

    public static function numberFormat($value = "", $dec = ",") {
        if (!$value) {
            return null;
        }
        return preg_replace("/[^0-9$dec]/", "", $value);
    }

    public static function fixHtml($str, $convert = "utf-8")
    {
        // pass it to the DOMDocument constructor
        $doc = new DOMDocument();

        // Must include the content-type/charset meta tag with $encoding
        // Bad HTML will trigger warnings, suppress those
        @$doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset='.$convert.'">' .$str);
        // extract the components we want
        $nodes = $doc->getElementsByTagName('body')->item(0)->childNodes;
        $html = '';
        $len = $nodes->length;
        for ($i = 0; $i < $len; $i++) {
            $html .= $doc->saveHTML($nodes->item($i));
        }
        return $html;
    }

    public static function fixXml($xml = "") {
        return preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $xml);
    }

    /**
     * Debug method to stop execution and inspect data
     * @param string $data
     * @param bool $special
     */
    public static function dd($data = "", $special = false)
    {
        if($special){
            $data = htmlspecialchars($data);
        }
        if(is_string($data)){
            die($data);
        }
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Return a temporary name to use as a filename
     * @param string $name
     * @return string
     */
    public static function tmpname($name = "download")
    {

        if(!is_string($name) || strlen($name) < 3){
            $name = 'download';
        }else{
            $name = preg_replace('/[^a-zA-ZΑ-Ωα-ω0-9_\-]/', '', strip_tags($name));
        }
        if(strlen($name) < 3){
            $name = 'download';
        }
        $name = tempnam(sys_get_temp_dir(), $name);
        if(!preg_match("/\.pdf$/", $name)){
            $name = $name.".pdf";
        }
        return $name;
    }

    /**
     * Shows the file in browser.
     * If remove after is not empty the file is destroyed afterwards.
     * If a filename is present, it is downloaded instead
     * @param $file
     * @param bool $removeAfter
     * @param string $filename
     */
    public static function showPdf($file, $filename = "", $removeAfter = false)
    {
        header("Content-type: application/pdf");
        if(!empty($filename)){
            if(!preg_match("/\.pdf$/", $filename)){
                $filename = $filename.".pdf";
            }
            header("Content-Disposition: attachment; filename=$filename");
        }
        echo file_get_contents($file);
        if(!empty($removeAfter)){
            unlink($file);
        }
        exit(0);
    }

    /**
     * Shortcut version for downloading the file
     * @param $file
     * @param string $filename
     * @param bool $removeAfter
     */
    public static function downloadPdf($file, $filename = "download", $removeAfter = false)
    {
        Crawler::showPdf($file, $filename, $removeAfter);
    }


    /**
     * Saves the file on disc and returns the path
     * @param string $file
     * @param string $downloadTo
     * @return string|null
     */
    public static function saveFile($file, $downloadTo = "")
    {
        if (empty($downloadTo)) {
            $downloadTo = self::tmpname();
        }
        try {
            $result = file_put_contents($downloadTo, file_get_contents($file));
            if ($result) {
                return $downloadTo;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}

