<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Service;

use SkyDiablo\SwiftmailerExtensionBundle\Plugin\CSS2Inline\CSS2InlineProcessorInterface;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;


/**
 * Description of CssToInlineStylesService
 *
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 */
class CssToInlineStylesService implements CSS2InlineProcessorInterface
{

    /**
     * @var CssToInlineStyles
     */
    private $worker;

    function __construct()
    {
        $this->worker = new CssToInlineStyles();
    }

    /**
     * Fetch HTML CSS to inline and returned the result
     *
     * @param string $html
     * @return string
     */
    public function process($html)
    {
        return $this->worker->convert($html);
    }
}