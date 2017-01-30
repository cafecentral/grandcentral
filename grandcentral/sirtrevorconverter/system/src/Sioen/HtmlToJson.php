<?php

// namespace Sioen;
//
// use Sioen\HtmlToJson\Converter;

include_once('HtmlToJson/StConverter.php');

/**
 * Class HtmlToJson
 *
 * Converts html to a json object that can be understood by Sir Trevor
 *
 * @version 1.1.0
 * @author Wouter Sioen <wouter@woutersioen.be>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
final class HtmlToJson
{
    /** @var array */
    private $converters = array();

    public function addConverter(StConverter $converter)
    {
        $this->converters[] = $converter;
    }

    /**
     * Converts html to the json Sir Trevor requires
     *
     * @param  string $html
     * @return string The json string
     */
    public function toJson($html)
    {
        // Strip white space between tags to prevent creation of empty #text nodes
        $html = preg_replace('~>\s+<~', '><', $html);
        $document = new \DOMDocument();

        // Load UTF-8 HTML hack (from http://bit.ly/pVDyCt)
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $document->encoding = 'UTF-8';
        // echo "<pre>";print_r($document);echo "</pre>";

        // fetch the body of the document. All html is stored in there
        $body = $document->getElementsByTagName("body")->item(0);
        // echo "<pre>";print_r($body);echo "</pre>";

        $data = array();

        // loop trough the child nodes and convert them
        if ($body) {
            foreach ($body->childNodes as $node) {
              if ($node->nodeType == 3) {
                $node->normalize();
              }
              $data[] = $this->convert($node);
            }
        }

        return json_encode(array('data' => $data));
    }

    /**
     * Converts one node to json
     *
     * @param \DOMElement $node
     * @return array
     */
    private function convert(\DOMElement $node)
    {
        foreach ($this->converters as $converter) {
            if ($converter->matches($node)) {
                return $converter->toJson($node);
            }
        }
    }
}
