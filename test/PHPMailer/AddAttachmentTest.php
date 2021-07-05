<?php

/**
 * PHPMailer - PHP email transport unit tests.
 * PHP version 5.5.
 *
 * @author    Marcus Bointon <phpmailer@synchromedia.co.uk>
 * @author    Andy Prevost
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2004 - 2009 Andy Prevost
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace PHPMailer\Test\PHPMailer;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\Test\SendTestCase;

/**
 * Test attachment adding functionality.
 */
final class AddAttachmentTest extends SendTestCase
{

    /**
     * Simple plain file attachment test.
     */
    public function testMultiplePlainFileAttachment()
    {
        $this->Mail->Body = 'Here is the text body';
        $this->Mail->Subject .= ': Plain + Multiple FileAttachments';

        if (!$this->Mail->addAttachment(realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer.png'))) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        if (!$this->Mail->addAttachment(__FILE__, 'test.txt')) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Rejection of non-local file attachments test.
     */
    public function testRejectNonLocalFileAttachment()
    {
        self::assertFalse(
            $this->Mail->addAttachment('https://github.com/PHPMailer/PHPMailer/raw/master/README.md'),
            'addAttachment should reject remote URLs'
        );

        self::assertFalse(
            $this->Mail->addAttachment('phar://phar.php'),
            'addAttachment should reject phar resources'
        );
    }

    /**
     * Simple HTML and attachment test.
     */
    public function testHTMLAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->Subject .= ': HTML + Attachment';
        $this->Mail->isHTML(true);
        $this->Mail->CharSet = 'UTF-8';

        if (
            !$this->Mail->addAttachment(
                realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
                'phpmailer_mini.png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        //Make sure phar paths are rejected
        self::assertFalse($this->Mail->addAttachment('phar://pharfile.php', 'pharfile.php'));
        //Make sure any path that looks URLish is rejected
        self::assertFalse($this->Mail->addAttachment('http://example.com/test.php', 'test.php'));
        self::assertFalse(
            $this->Mail->addAttachment(
                'ssh2.sftp://user:pass@attacker-controlled.example.com:22/tmp/payload.phar',
                'test.php'
            )
        );
        self::assertFalse($this->Mail->addAttachment('x-1.cd+-://example.com/test.php', 'test.php'));

        //Make sure that trying to attach a nonexistent file fails
        $filename = __FILE__ . md5(microtime()) . 'nonexistent_file.txt';
        self::assertFalse($this->Mail->addAttachment($filename));
        //Make sure that trying to attach an existing but unreadable file fails
        touch($filename);
        chmod($filename, 0200);
        self::assertFalse($this->Mail->addAttachment($filename));
        chmod($filename, 0644);
        unlink($filename);

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Attachment naming test.
     */
    public function testAttachmentNaming()
    {
        $this->Mail->Body = 'Attachments.';
        $this->Mail->Subject .= ': Attachments';
        $this->Mail->isHTML(true);
        $this->Mail->CharSet = 'UTF-8';
        $this->Mail->addAttachment(
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
            'phpmailer_mini.png";.jpg'
        );
        $this->Mail->addAttachment(
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer.png'),
            'phpmailer.png'
        );
        $this->Mail->addAttachment(
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/PHPMailer card logo.png'),
            'PHPMailer card logo.png'
        );
        $this->Mail->addAttachment(
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
            'phpmailer_mini.png\\\";.jpg'
        );
        $this->buildBody();
        $this->Mail->preSend();
        $message = $this->Mail->getSentMIMEMessage();
        self::assertStringContainsString(
            'Content-Type: image/png; name="phpmailer_mini.png\";.jpg"',
            $message,
            'Name containing double quote should be escaped in Content-Type'
        );
        self::assertStringContainsString(
            'Content-Disposition: attachment; filename="phpmailer_mini.png\";.jpg"',
            $message,
            'Filename containing double quote should be escaped in Content-Disposition'
        );
        self::assertStringContainsString(
            'Content-Type: image/png; name=phpmailer.png',
            $message,
            'Name without special chars should not be quoted in Content-Type'
        );
        self::assertStringContainsString(
            'Content-Disposition: attachment; filename=phpmailer.png',
            $message,
            'Filename without special chars should not be quoted in Content-Disposition'
        );
        self::assertStringContainsString(
            'Content-Type: image/png; name="PHPMailer card logo.png"',
            $message,
            'Name with spaces should be quoted in Content-Type'
        );
        self::assertStringContainsString(
            'Content-Disposition: attachment; filename="PHPMailer card logo.png"',
            $message,
            'Filename with spaces should be quoted in Content-Disposition'
        );
    }

    /**
     * Simple HTML and multiple attachment test.
     */
    public function testHTMLMultiAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->Subject .= ': HTML + multiple Attachment';
        $this->Mail->isHTML(true);

        if (
            !$this->Mail->addAttachment(
                realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
                'phpmailer_mini.png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        if (
            !$this->Mail->addAttachment(
                realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer.png'),
                'phpmailer.png'
            )
        ) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Expect exceptions on bad encoding
     */
    public function testAddAttachmentEncodingException()
    {
        $this->expectException(Exception::class);

        $mail = new PHPMailer(true);
        $mail->addAttachment(__FILE__, 'test.txt', 'invalidencoding');
    }

    /**
     * Expect exceptions on sending after deleting a previously successfully attached file
     */
    public function testDeletedAttachmentException()
    {
        $this->expectException(Exception::class);

        $filename = __FILE__ . md5(microtime()) . 'test.txt';
        touch($filename);
        $this->Mail = new PHPMailer(true);
        $this->Mail->addAttachment($filename);
        unlink($filename);
        $this->Mail->send();
    }

    /**
     * Expect error on sending after deleting a previously successfully attached file
     */
    public function testDeletedAttachmentError()
    {
        $filename = __FILE__ . md5(microtime()) . 'test.txt';
        touch($filename);
        $this->Mail = new PHPMailer();
        $this->Mail->addAttachment($filename);
        unlink($filename);
        self::assertFalse($this->Mail->send());
    }
}
