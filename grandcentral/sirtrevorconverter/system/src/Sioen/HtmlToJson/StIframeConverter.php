<?php

// namespace Sioen\HtmlToJson;
//
// use Sioen\SirTrevorBlock;

include_once('StConverter.php');
include_once('StHtmlToMarkdown.php');

final class StIframeConverter implements StConverter
{
    use StHtmlToMarkdown;

    public function toJson(\DOMElement $node)
    {
        $html = $node->ownerDocument->saveXML($node);

        // youtube or vimeo
        if (preg_match('~//www.youtube.com/embed/([^/\?]+).*\"~si', $html, $matches)) {
            return new SirTrevorBlock(
                'video',
                array(
                    'source' => 'youtube',
                    'remote_id' => $matches[1],
                )
            );
        }

        if (preg_match('~//player.vimeo.com/video/([^/\?]+).*\?~si', $html, $matches)) {
            return new SirTrevorBlock(
                'video',
                array(
                    'source' => 'vimeo',
                    'remote_id' => $matches[1],
                )
            );
        }
    }

    public function matches(\DomElement $node)
    {
        return $node->nodeName === 'iframe';
    }
}
