<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Plugin\CSS2Inline;

/**
 * CSS to Inline Processor Interface
 * 
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 */
interface CSS2InlineProcessorInterface {

    /**
     * Fetch HTML CSS to inline and returned the result
     * @param string $html
     *
     * @return string
     */
    public function process($html);

}