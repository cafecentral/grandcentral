<?php

namespace Sioen\Tests;

use Sioen\JsonToHtml;
use Sioen\JsonToHtml\BlockquoteConverter;
use Sioen\JsonToHtml\BaseConverter;
use Sioen\JsonToHtml\IframeConverter;
use Sioen\JsonToHtml\ImageConverter;
use Sioen\JsonToHtml\HeadingConverter;

final class JsonToHtmlTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertBlockquoteToHtml()
    {
        $converter = new JsonToHtml();
        $converter->addConverter(new BlockquoteConverter());

        $json = json_encode(array(
            'data' => array(array(
                'type' => 'quote',
                'data' => array(
                    'text' => 'Text',
                    'cite' => 'Cite'
                )
            ))
        ));
        $html = $converter->toHtml($json);
        $this->assertEquals(
            $html,
            "<blockquote><p>Text</p>\n<cite><p>Cite</p>\n</cite></blockquote>"
        );
    }

    public function testConvertParagraphToHtml()
    {
        $converter = new JsonToHtml();
        $converter->addConverter(new BaseConverter());

        // Lets convert a normal text type that uses the default converter
        $json = json_encode(array(
            'data' => array(array(
                'type' => 'text',
                'data' => array(
                    'text' => 'test'
                )
            ))
        ));
        $html = $converter->toHtml($json);
        $this->assertEquals($html, "<p>test</p>\n");
    }

    public function testConvertVideoToHtml()
    {
        $converter = new JsonToHtml();
        $converter->addConverter(new IframeConverter());

        // the video conversion
        $json = json_encode(array(
            'data' => array(array(
                'type' => 'video',
                'data' => array(
                    'source' => 'youtube',
                    'remote_id' => '4BXpi7056RM'
                )
            ))
        ));
        $html = $converter->toHtml($json);
        $this->assertEquals(
            $html,
            "<iframe src=\"//www.youtube.com/embed/4BXpi7056RM?rel=0\" frameborder=\"0\" allowfullscreen></iframe>\n"
        );
    }

    public function testConvertHeadingToHtml()
    {
        $converter = new JsonToHtml();
        $converter->addConverter(new HeadingConverter());

        // The heading
        $json = json_encode(array(
            'data' => array(array(
                'type' => 'heading',
                'data' => array(
                    'text' => 'test'
                )
            ))
        ));
        $html = $converter->toHtml($json);
        $this->assertEquals($html, "<h2>test</h2>\n");
    }

    public function testConvertImageToHtml()
    {
        $converter = new JsonToHtml();
        $converter->addConverter(new ImageConverter());

        // images
        $json = json_encode(array(
            'data' => array(array(
                'type' => 'image',
                'data' => array(
                    'file' => array(
                        'url' => '/frontend/files/sir-trevor/images/IMG_3867.JPG'
                    )
                )
            ))
        ));
        $html = $converter->toHtml($json);
        $this->assertEquals($html, "<img src=\"/frontend/files/sir-trevor/images/IMG_3867.JPG\" />\n");
    }
}
