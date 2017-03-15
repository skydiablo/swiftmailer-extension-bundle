<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Service;

use SkyDiablo\SwiftmailerExtensionBundle\Exception\InvalidEmailAddressException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Description of DefaultMailerService
 *
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 */
class DefaultMailerService
{

    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_TEXT_PLAIN = 'text/plain';

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $senderEmailAddress;

    /**
     * @var string
     */
    protected $senderEmailName;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var bool
     */
    private $strictEmailValidation = true;

    /**
     * @var FileLocatorInterface
     */
    private $fileLocator;

    /**
     * @param string $recipientEmailAddress
     * @param string $recipientName
     * @param string $subjectTemplate
     * @param string $bodyTemplate
     * @param array $parameters
     * @param string $language
     * @param string $bodyCharset
     * @return bool
     */
    public function sendEmail(
        string $recipientEmailAddress,
        string $recipientName = null,
        string $subjectTemplate,
        string $bodyTemplate,
        array $parameters = [],
        string $language = null,
        string $bodyCharset = DefaultMailerService::CONTENT_TYPE_TEXT_HTML
    )
    {
        $message = $this->generateMessage($recipientEmailAddress, $recipientName, $subjectTemplate, $bodyTemplate, $parameters, $language, $bodyCharset);
        return (bool)$this->mailer->send($message);
    }

    /**
     * @param string $recipientEmailAddress
     * @param string $recipientName
     * @param string $subjectTemplate
     * @param string $bodyTemplate
     * @param array $parameters
     * @param string $language
     * @param string $bodyCharset
     * @return \Swift_Message
     */
    protected function generateMessage(
        string $recipientEmailAddress,
        string $recipientName = null,
        string $subjectTemplate,
        string $bodyTemplate,
        array $parameters = [],
        string $language = null,
        string $bodyCharset = DefaultMailerService::CONTENT_TYPE_TEXT_HTML
    )
    {
        $this->validateEmailAddress($recipientEmailAddress);

        $oldLocale = $this->translator->getLocale();
        try {
            $this->translator->setLocale($language ?: $oldLocale);
        } catch (\InvalidArgumentException $e) {
            //ignore
        }
        try {
            $subject = $this->renderTemplate($subjectTemplate, $parameters);
            $bodyHtml = $this->renderTemplate($bodyTemplate, $parameters);
        } finally {
            try {
                $this->translator->setLocale($oldLocale);
            } catch (\InvalidArgumentException $e) {
                //ignore
            }
        }

        return \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
            ->setTo($recipientEmailAddress, $recipientName ?: null)
            ->setBody($bodyHtml, $bodyCharset);
    }

    /**
     * @param string $text twig template as string
     * @param array $context twig template parameter
     * @return string
     */
    protected function renderTemplate(string $text, array $context = [])
    {
        try {
            $res = $this->fileLocator->locate($text);
        } catch (FileLocatorFileNotFoundException $e) {
            $res = false;
        }
        if ($res) { // is local resource ?
            return $this->twig->render($text, $context);
        } else {
            $template = $this->twig->createTemplate($text);
            return $template->render($context);
        }
    }

    /**
     * @return string
     */
    public function getSenderEmailAddress()
    {
        return $this->senderEmailAddress;
    }

    /**
     * @param string $senderEmailAddress
     * @return $this
     */
    public function setSenderEmailAddress($senderEmailAddress)
    {
        $this->validateEmailAddress($senderEmailAddress);
        $this->senderEmailAddress = (string)$senderEmailAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getSenderEmailName()
    {
        return $this->senderEmailName;
    }

    /**
     * @param string $senderEmailName
     * @return $this
     */
    public function setSenderEmailName($senderEmailName)
    {
        $this->senderEmailName = (string)$senderEmailName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStrictEmailValidation(): bool
    {
        return $this->strictEmailValidation;
    }

    /**
     * @param bool $strictEmailValidation
     * @return DefaultMailerService
     */
    public function setStrictEmailValidation(bool $strictEmailValidation): DefaultMailerService
    {
        $this->strictEmailValidation = $strictEmailValidation;
        return $this;
    }


    /**
     * @param string $email
     * @return bool
     * @throws InvalidEmailAddressException
     */
    public function validateEmailAddress(string $email)
    {
        // strict mode enabled: more than just this regex: "/^.+\@\S+\.\S+$/"
        $constraint = new Email(['strict' => $this->strictEmailValidation]);
        $error = $this->validator->validate($email, $constraint);
        if ($error->count() > 0) {
            throw InvalidEmailAddressException::create($email);
        }
        return true;
    }

    /**
     * @param \Swift_Mailer $mailer
     * @return DefaultMailerService
     */
    public function setMailer(\Swift_Mailer $mailer): DefaultMailerService
    {
        $this->mailer = $mailer;
        return $this;
    }

    /**
     * @param \Twig_Environment $twig
     * @return DefaultMailerService
     */
    public function setTwig(\Twig_Environment $twig): DefaultMailerService
    {
        $this->twig = $twig;
        return $this;
    }

    /**
     * @param TranslatorInterface $translator
     * @return DefaultMailerService
     */
    public function setTranslator(TranslatorInterface $translator): DefaultMailerService
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * @param ValidatorInterface $validator
     * @return DefaultMailerService
     */
    public function setValidator(ValidatorInterface $validator): DefaultMailerService
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @param FileLocatorInterface $fileLocator
     * @return DefaultMailerService
     */
    public function setFileLocator(FileLocatorInterface $fileLocator): DefaultMailerService
    {
        $this->fileLocator = $fileLocator;
        return $this;
    }

}