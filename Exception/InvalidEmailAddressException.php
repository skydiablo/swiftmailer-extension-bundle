<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Exception;

/**
 * Description for class InvalidEmailAddressException
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 */
class InvalidEmailAddressException extends \Exception {

    const INVALID_EMAIL_ADDRESS = 15300;

    public static function create($email) {
        return new static(sprintf('Invalid email address: "%s"', $email), self::INVALID_EMAIL_ADDRESS);
    }

}