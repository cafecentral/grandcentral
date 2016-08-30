<?php

// namespace League\HTMLToMarkdown\Converter;

include_once('ConverterInterface.php');

class DivConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function setConfig(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param ElementInterface $element
     *
     * @return string
     */
    public function convert(ElementInterface $element)
    {
        if ($this->config->getOption('strip_tags', false)) {
            return $element->getValue() . "\n\n";
        }

        return html_entity_decode($element->getChildrenAsString());
    }

    /**
     * @return string[]
     */
    public function getSupportedTags()
    {
        return array('div');
    }
}
