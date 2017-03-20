<?php


namespace SkyDiablo\SwiftmailerExtensionBundle\Model;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class BouncedEmail
 */
class EmailReturnStatus
{

    const STATUS_UNKNOWN = 'unknown';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_TRANSIENT = 'transient';
    const STATUS_COMPLAINT = 'complaint';

    private $status;

    /**
     * sender email address
     * @var string
     */
    private $sender;

    /**
     * array of recipient email addresses
     * @var string[]
     */
    private $recipients;

    /**
     * @var \DateTime
     */
    private $timestamp;

    /**
     * EmailReturnStatus constructor.
     * @param string $status
     * @param string $sender
     * @param \string[] $recipients
     * @param \DateTime $timestamp
     */
    protected function __construct(string $status, string $sender, array $recipients, \DateTime $timestamp = null)
    {
        $this->status = $status;
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->timestamp = $timestamp ?: new \DateTime();
    }

    /**
     * @param string $status
     * @param string $sender
     * @param array $recipients
     * @param \DateTime $timestamp
     * @return EmailReturnStatus
     */
    public static function create(string $status, string $sender, array $recipients, \DateTime $timestamp = null)
    {
        return new self($status, $sender, $recipients, $timestamp);
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @return \string[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

}