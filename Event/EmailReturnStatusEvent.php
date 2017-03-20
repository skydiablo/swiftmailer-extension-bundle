<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Event;

use SkyDiablo\SwiftmailerExtensionBundle\Model\EmailReturnStatus;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class EmailBouncedEvent
 */
class EmailReturnStatusEvent extends Event
{

    const NAME = 'skydiablo.swiftmailer.extension.email-return-status';

    /**
     * @var EmailReturnStatus
     */
    private $emailReturnStatus;

    /**
     * BouncedEmailsEvent constructor.
     * @param EmailReturnStatus $emailReturnStatus
     */
    protected function __construct(EmailReturnStatus $emailReturnStatus)
    {
        $this->emailReturnStatus = $emailReturnStatus;
    }

    /**
     * @param EmailReturnStatus $emailReturnStatus
     * @return EmailReturnStatusEvent
     */
    public static function create(EmailReturnStatus $emailReturnStatus)
    {
        return new self($emailReturnStatus);
    }

    /**
     * @return EmailReturnStatus
     */
    public function getEmailReturnStatus(): EmailReturnStatus
    {
        return $this->emailReturnStatus;
    }

}