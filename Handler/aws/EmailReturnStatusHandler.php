<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Handler\aws;

use Aws\Sqs\SqsClient;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use SkyDiablo\SwiftmailerExtensionBundle\Event\EmailReturnStatusEvent;
use SkyDiablo\SwiftmailerExtensionBundle\Model\EmailReturnStatus;
use SnapMe\CoreBundle\Service\Logger\LoggableInterface;
use SnapMe\CoreBundle\Service\Logger\LoggableTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class BouncesAndComplaintsHandler
 */
class EmailReturnStatusHandler
{

    const DEFAULT_AWS_SQS_LONG_POLLING_TIMEOUT = 10;
    const LOG_MESSAGE_PREFIX = 'Swiftmailer-Extension EmailReturnStatusHandler =>';

    /**
     * @var string
     */
    private $queueUrl;

    /**
     * @var SqsClient
     */
    private $sqs;

    /**
     * @var int
     */
    private $defaultLongPollingTimeout;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * BouncesAndComplaintsHandler constructor.
     * @param string $queueUrl
     * @param SqsClient $sqs
     * @param int $defaultLongPollingTimeout
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        SqsClient $sqs,
        string $queueUrl,
        int $defaultLongPollingTimeout,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger)
    {
        $this->queueUrl = $queueUrl;
        $this->sqs = $sqs;
        $this->defaultLongPollingTimeout = $defaultLongPollingTimeout;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @param int $count
     * @param int $longPollingTimeout
     * @return int
     * @throws \Exception
     */
    public function run(int $count, int $longPollingTimeout = null)
    {
        $handledCount = 0;
        $options = [
            'MaxNumberOfMessages' => $count,
            'WaitTimeSeconds' => $longPollingTimeout ?: $this->defaultLongPollingTimeout,
            'QueueUrl' => $this->queueUrl,
        ];

        try {
            $response = $this->sqs->receiveMessage($options);
        } catch (\Exception $e) {
            $this->logError('Load messages from SQS', $e);
            return $handledCount;
        }

        $messages = $response->get('Messages');
        if (is_array($messages) && count($messages)) {
            foreach ($messages as $sqsMessage) {
                try {
                    if ($message = json_decode((string)json_decode((string)($sqsMessage['Body'] ?? null), true)['Message'] ?? null, true)) {
                        $bouncedEmails = EmailReturnStatus::create( // deserialize to BouncedEmails object
                            $this->normalizeMessageType($message),
                            $message['mail']['source'] ?? '',
                            (array)($message['mail']['destination'] ?? null),
                            \DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $message['mail']['timestamp'] ?? null) ?: null
                        );
                        $event = EmailReturnStatusEvent::create($bouncedEmails); // create event object
                        $this->eventDispatcher->dispatch( // dispatch event
                            EmailReturnStatusEvent::NAME,
                            $event
                        );
                        $handledCount++;
                        continue;
                    }
                    $this->logError(sprintf('Invalid SQS message: %s', $sqsMessage));
                } finally {
                    $this->removeSqsMessage((string)$sqsMessage['ReceiptHandle']); // remove message from SQS queue
                }
            }
        }
        return $handledCount;
    }

    /**
     * @param $message
     * @return string
     */
    protected function normalizeMessageType(array $message)
    {
        $type = $message['notificationType'] ?? EmailReturnStatus::STATUS_UNKNOWN;
        switch ($type) {
            case 'Bounce':
                if (($message['bounce']['bounceType'] ?? null) === 'Transient') {
                    return EmailReturnStatus::STATUS_TRANSIENT;
                }
                return EmailReturnStatus::STATUS_BOUNCED;
            case 'Complaint':
                return EmailReturnStatus::STATUS_COMPLAINT;
            default:
                return $type;
        }
    }

    /**
     * Remove the message with the given receipt handle.
     *
     * @param string $receiptHandle
     *
     * @return boolean True if the remove was successful; otherwise false
     */
    protected function removeSqsMessage($receiptHandle)
    {
        try {
            $response = $this->sqs->deleteMessage([
                'QueueUrl' => $this->queueUrl,
                'ReceiptHandle' => $receiptHandle
            ]);
        } catch (\Exception $e) {
            $this->logError('Remove message from SQS', $e);
            return false;
        }
        return true;
    }

    /**
     * @param string $message
     * @param \Exception $e
     */
    protected function logError($message, \Exception $e = null)
    {
        $this->logger->error(sprintf('%s %s: %s', self::LOG_MESSAGE_PREFIX, $message, $e->getMessage()), [$e]);
    }

}