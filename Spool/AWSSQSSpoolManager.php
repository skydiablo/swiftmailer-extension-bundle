<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Spool;

use Psr\Log\LoggerInterface;
use Swift_Mime_Message;
use Swift_Transport;
use Aws\Sqs\SqsClient;


/**
 * Description of AWSSQSSpoolManager
 *
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 */
class AWSSQSSpoolManager extends \Swift_ConfigurableSpool
{

    const LOG_MESSAGE_PREFIX = 'Swiftmailer AWS-SQS Spool-Manager =>';

    /**
     * @var integer The minimum number of messages that may be fetched at one time
     */
    const MIN_NUMBER_MESSAGES = 1;

    /**
     * @var integer The maximum number of messages that may be fetched at one time
     */
    const MAX_NUMBER_MESSAGES = 10;

    /**
     * @var int default value
     */
    const DEFAULT_AWS_SQS_LONG_POLLING_TIMEOUT = 10; //in seconds

    /**
     * @var SqsClient
     */
    private $sqs;

    /**
     * @var array
     */
    protected $queueOptions;

    /**
     * @var string
     */
    protected $queueUrl;

    /**
     * @var integer The maximum size (in KB) of a message in the queue
     */
    protected $maxMessageSize;

    /**
     * @var int SQS also supports "long polling", meaning that you can instruct SQS to hold the connection open with the SDK for up to 20 seconds in order to wait for a message to arrive in the queue.
     */
    protected $longPollingTimeout;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param SqsClient $sqs
     * @param array $queueOptions
     * @param LoggerInterface $logger
     */
    public function __construct(SqsClient $sqs, array $queueOptions, LoggerInterface $logger)
    {
        $this->sqs = $sqs;
        $this->queueOptions = $queueOptions;
        $this->logger = $logger;

        foreach (['url', 'max_message_size'] as $key) {
            if (!isset($queueOptions[$key])) {
                throw new \InvalidArgumentException(sprintf('The queue option "%s" must be provided.', $key));
            }
        }

        $this->queueUrl = $queueOptions['url'];
        $this->maxMessageSize = $queueOptions['max_message_size'];
        $this->longPollingTimeout = isset($queueOptions['long_polling_timeout']) ? $queueOptions['long_polling_timeout'] : self::DEFAULT_AWS_SQS_LONG_POLLING_TIMEOUT;
    }

    /**
     * Starts this Spool mechanism.
     */
    public function start()
    {
    }

    /**
     * Stops this Spool mechanism.
     */
    public function stop()
    {
    }

    /**
     * Tests if this Spool mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Queues a message.
     *
     * @param Swift_Mime_Message $message The message to store
     *
     * @return bool    Whether the operation has succeeded
     */
    public function queueMessage(Swift_Mime_Message $message)
    {
        $messageBody = base64_encode(gzcompress(serialize($message)));

        if (strlen($messageBody) > $this->maxMessageSize * 1024) {
            return false;
        }

        try {
            $response = $this->sqs->sendMessage([
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => $messageBody
            ]);
        } catch (\Exception $e) {
            $this->logError('Store messages to SQS', $e);
            return false;
        }

        return true;
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param Swift_Transport $transport A transport instance
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int     The number of sent emails
     */
    public function flushQueue(Swift_Transport $transport, &$failedRecipients = null)
    {
        $failedRecipients = (array)$failedRecipients;
        $count = 0;
        $time = time();

        $maxNumberOfMessages = max(
            min(
                $this->getMessageLimit(),
                static::MAX_NUMBER_MESSAGES
            ),
            static::MIN_NUMBER_MESSAGES
        );

        $options = [
            'MaxNumberOfMessages' => $maxNumberOfMessages,
            'WaitTimeSeconds' => $this->longPollingTimeout
        ];

        while (true) {
            try {
                $response = $this->sqs->receiveMessage([
                        'QueueUrl' => $this->queueUrl,
                    ] + $options);
            } catch (\Exception $e) {
                $this->logError('Load messages from SQS', $e);
                return $count;
            }

            $messages = $response->get('Messages');
            if (!is_array($messages) || count($messages) == 0) {
                return $count;
            }

            foreach ($messages as $sqsMessage) {
                if (!$transport->isStarted()) {
                    $transport->start();
                }

                $message = unserialize(gzuncompress(base64_decode((string)$sqsMessage['Body'])));

                try {
                    $count += $transport->send($message, $failedRecipients);
                } catch (\Exception $e) {
                    $this->logError('Send e-mail', $e);
                }

                $this->removeSqsMessage((string)$sqsMessage['ReceiptHandle']);

                if ($this->getMessageLimit() && ($count >= $this->getMessageLimit())) {
                    return $count;
                }

                if ($this->getTimeLimit() && ((time() - $time) >= $this->getTimeLimit())) {
                    return $count;
                }
            }
        }

        return $count;
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
    protected function logError($message, \Exception $e)
    {
        $this->logger->error(sprintf('%s %s: %s', self::LOG_MESSAGE_PREFIX, $message, $e->getMessage()), [$e]);
    }
}