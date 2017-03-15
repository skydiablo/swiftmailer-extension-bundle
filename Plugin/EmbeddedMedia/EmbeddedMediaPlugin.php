<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Plugin\EmbeddedMedia;

use Swift_Events_SendEvent;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Description of EmbeddedMediaPlugin
 *
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 */
class EmbeddedMediaPlugin implements \Swift_Events_SendListener {

    /**
     * @var string
     */
    private $embedAttributeName;

    /**
     * @var bool
     */
    private $embedByDefault;

    /**
     * @param $embedAttributeName
     * @param $embedByDefault
     */
    function __construct(string $embedAttributeName, bool $embedByDefault) {
        $this->embedAttributeName = $embedAttributeName;
        $this->embedByDefault = $this->convertToBoolean($embedByDefault);
    }

    /**
     * @param $input
     * @return bool
     */
    protected function convertToBoolean($input) {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt) {
        if (!$this->embedAttributeName && !$this->embedByDefault) {
            return; //embaddeding not possible
        }

        $parts = array_merge([$evt->getMessage()], $evt->getMessage()->getChildren());

        foreach ($parts AS $part) {
            if ($part instanceof \Swift_Mime_SimpleMessage) {
                $this->processMimeEntity($part);
            }
        }
    }

    /**
     * @param \Swift_Mime_SimpleMessage $entity
     *
     * @return \Swift_Mime_Message
     */
    protected function processMimeEntity(\Swift_Mime_SimpleMessage $entity) {

        $crawler = new Crawler($entity->getBody()); //create DOM-Crawler

        try {
            //extract a base path for media objects
            $baseURL = $crawler->filterXPath('//head/base[@href]')->attr('href');
        } catch (\InvalidArgumentException $e) {
            $baseURL = null;
        }

        $changed = false;

        /** @var \DOMElement $node */
        foreach ($crawler->filterXPath('//img[@src]') AS $node) { //select all img-tags with src attribute
            $src = $node->getAttribute('src'); //get image source
            if (!(stripos($src, 'cid:') === 0)) {
                $embed = $this->embedByDefault;
                if ($this->embedAttributeName && $node->hasAttribute($this->embedAttributeName)) { //has tag a embed-attribute ?
                    $embed = $this->convertToBoolean($node->getAttribute($this->embedAttributeName)); //tag can decide by its own
                }
                if ($embed) {
                    try {
                        $source = $this->getSource($baseURL, $src);
                        $imageEntity = \Swift_Image::fromPath($source);
                        $cid = $entity->embed($imageEntity); //embed image binary
                        $node->setAttributeNode(new \DOMAttr('src', $cid)); //exchange the original source with embedded cid-link
                        $changed = true;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        if ($changed) {
            $entity->setBody($crawler->html()); //assign new body
        }
        return $entity;
    }

    /**
     * add base-path to the source
     *
     * @param string $base
     * @param string $source
     *
     * @return string
     */
    protected function getSource($base, $source) {
        if ($base) {
            if (!(stripos($source, 'http') === 0) && !(stripos($source, 'cid:') === 0)) {
                $source = sprintf('%s/%s', rtrim($base, '/'), ltrim($source, '/'));
            }
        }
        return $source;
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function sendPerformed(Swift_Events_SendEvent $evt) {
        // do nothing
    }
}