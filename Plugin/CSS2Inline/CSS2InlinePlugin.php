<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Plugin\CSS2Inline;

use Swift_Events_SendEvent;


/**
 * Description of CSS2InlinePlugin
 *
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 */
class CSS2InlinePlugin implements \Swift_Events_SendListener {

    /**
     * @var CSS2InlineProcessorInterface
     */
    private $cssToInlineService;

    /**
     * @param CSS2InlineProcessorInterface $cssToInlineService
     */
    function __construct(CSS2InlineProcessorInterface $cssToInlineService) {
        $this->cssToInlineService = $cssToInlineService;
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt) {
        $message = $evt->getMessage();
        //process the main body and all mime-childs
        $this->processMimeEntity($message);
        foreach ($message->getChildren() AS $entity) {
            if ($entity instanceof \Swift_Mime_Message) {
                $this->processMimeEntity($entity);
            }
        }
    }

    /**
     * @param \Swift_Mime_MimeEntity $entity
     *
     * @return \Swift_Mime_MimeEntity
     */
    protected function processMimeEntity(\Swift_Mime_MimeEntity $entity) {
        if ($body = trim($entity->getBody())) {
            $entity->setBody(
                $this->cssToInlineService->process(
                    $body
                )
            );
        }
        return $entity;
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function sendPerformed(Swift_Events_SendEvent $evt) {
        //do nothing
    }
}