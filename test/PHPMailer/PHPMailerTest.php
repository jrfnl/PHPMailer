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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\Test\SendTestCase;

/**
 * PHPMailer - PHP email transport unit test class.
 */
final class PHPMailerTest extends SendTestCase
{
    /**
     * Test low priority.
     */
    public function testLowPriority()
    {
        $this->Mail->Priority = 5;
        $this->Mail->Body = 'Here is the main body.  There should be ' .
            'a reply to address in this message.';
        $this->Mail->Subject .= ': Low Priority';

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Plain quoted-printable message.
     */
    public function testQuotedPrintable()
    {
        $this->Mail->Body = 'Here is the main body';
        $this->Mail->Subject .= ': Plain + Quoted-printable';
        $this->Mail->Encoding = 'quoted-printable';

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Check that a quoted printable encode and decode results in the same as went in
        $t = file_get_contents(__FILE__); //Use this file as test content
        //Force line breaks to UNIX-style
        $t = str_replace(["\r\n", "\r"], "\n", $t);
        self::assertSame(
            $t,
            quoted_printable_decode($this->Mail->encodeQP($t)),
            'Quoted-Printable encoding round-trip failed'
        );
        //Force line breaks to Windows-style
        $t = str_replace("\n", "\r\n", $t);
        self::assertSame(
            $t,
            quoted_printable_decode($this->Mail->encodeQP($t)),
            'Quoted-Printable encoding round-trip failed (Windows line breaks)'
        );
    }

    /**
     * Test header encoding & folding.
     */
    public function testHeaderEncoding()
    {
        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;
        $letter = html_entity_decode('&eacute;', ENT_COMPAT, PHPMailer::CHARSET_UTF8);
        //This should select B-encoding automatically and should fold
        $bencode = str_repeat($letter, PHPMailer::STD_LINE_LENGTH + 1);
        //This should select Q-encoding automatically and should fold
        $qencode = str_repeat('e', PHPMailer::STD_LINE_LENGTH) . $letter;
        //This should select B-encoding automatically and should not fold
        $bencodenofold = str_repeat($letter, 10);
        //This should select Q-encoding automatically and should not fold
        $qencodenofold = str_repeat('e', 9) . $letter;
        //This should Q-encode as ASCII and fold (previously, this did not encode)
        $longheader = str_repeat('e', PHPMailer::STD_LINE_LENGTH + 10);
        //This should Q-encode as UTF-8 and fold
        $longutf8 = str_repeat($letter, PHPMailer::STD_LINE_LENGTH + 10);
        //This should not change
        $noencode = 'eeeeeeeeee';
        $this->Mail->isMail();
        //Expected results

        $bencoderes = '=?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
            PHPMailer::getLE() .
            ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
            PHPMailer::getLE() .
            ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
            PHPMailer::getLE() .
            ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqQ==?=';
        $qencoderes = '=?utf-8?Q?eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee?=' .
            PHPMailer::getLE() .
            ' =?utf-8?Q?eeeeeeeeeeeeeeeeeeeeeeeeee=C3=A9?=';
        $bencodenofoldres = '=?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6k=?=';
        $qencodenofoldres = '=?utf-8?Q?eeeeeeeee=C3=A9?=';
        $longheaderres = '=?us-ascii?Q?eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee?=' .
            PHPMailer::getLE() . ' =?us-ascii?Q?eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee?=';
        $longutf8res = '=?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
             PHPMailer::getLE() . ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
             PHPMailer::getLE() . ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6k=?=' .
             PHPMailer::getLE() . ' =?utf-8?B?w6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqcOpw6nDqQ==?=';
        $noencoderes = 'eeeeeeeeee';
        self::assertSame(
            $bencoderes,
            $this->Mail->encodeHeader($bencode),
            'Folded B-encoded header value incorrect'
        );
        self::assertSame(
            $qencoderes,
            $this->Mail->encodeHeader($qencode),
            'Folded Q-encoded header value incorrect'
        );
        self::assertSame(
            $bencodenofoldres,
            $this->Mail->encodeHeader($bencodenofold),
            'B-encoded header value incorrect'
        );
        self::assertSame(
            $qencodenofoldres,
            $this->Mail->encodeHeader($qencodenofold),
            'Q-encoded header value incorrect'
        );
        self::assertSame(
            $longheaderres,
            $this->Mail->encodeHeader($longheader),
            'Long header value incorrect'
        );
        self::assertSame(
            $longutf8res,
            $this->Mail->encodeHeader($longutf8),
            'Long UTF-8 header value incorrect'
        );
        self::assertSame(
            $noencoderes,
            $this->Mail->encodeHeader($noencode),
            'Unencoded header value incorrect'
        );
    }

    /**
     * Send an HTML message.
     */
    public function testHtml()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': HTML only';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>HTML email test</title>
    </head>
    <body>
        <h1>PHPMailer does HTML!</h1>
        <p>This is a <strong>test message</strong> written in HTML.<br>
        Go to <a href="https://github.com/PHPMailer/PHPMailer/">https://github.com/PHPMailer/PHPMailer/</a>
        for new versions of PHPMailer.</p>
        <p>Thank you!</p>
    </body>
</html>
EOT;
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
    }

    /**
     * Send an HTML message specifying the DSN notifications we expect.
     */
    public function testDsn()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': HTML only';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>HTML email test</title>
    </head>
    <body>
        <p>PHPMailer</p>
    </body>
</html>
EOT;
        $this->buildBody();
        $this->Mail->dsn = 'SUCCESS,FAILURE';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        //Sends the same mail, but sets the DSN notification to NEVER
        $this->Mail->dsn = 'NEVER';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * createBody test of switch case
     */
    public function testCreateBody()
    {
        $PHPMailer = new PHPMailer();
        $reflection = new \ReflectionClass($PHPMailer);
        $property = $reflection->getProperty('message_type');
        $property->setAccessible(true);
        $property->setValue($PHPMailer, 'inline');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'attach');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'inline_attach');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt_inline');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt_attach');
        self::assertIsString($PHPMailer->createBody());

        $property->setValue($PHPMailer, 'alt_inline_attach');
        self::assertIsString($PHPMailer->createBody());
    }

    /**
     * Send a message containing ISO-8859-1 text.
     */
    public function testHtmlIso8859()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': ISO-8859-1 HTML';
        $this->Mail->CharSet = PHPMailer::CHARSET_ISO88591;

        //This file is in ISO-8859-1 charset
        //Needs to be external because this file is in UTF-8
        $content = file_get_contents(realpath(\PHPMAILER_INCLUDE_DIR . '/examples/contents.html'));
        //This is the string 'éèîüçÅñæß' in ISO-8859-1, base-64 encoded
        $check = base64_decode('6eju/OfF8ebf');
        //Make sure it really is in ISO-8859-1!
        $this->Mail->msgHTML(
            mb_convert_encoding(
                $content,
                'ISO-8859-1',
                mb_detect_encoding($content, 'UTF-8, ISO-8859-1, ISO-8859-15', true)
            ),
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples')
        );
        $this->buildBody();
        self::assertStringContainsString($check, $this->Mail->Body, 'ISO message body does not contain expected text');
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Send a message containing multilingual UTF-8 text.
     */
    public function testHtmlUtf8()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': UTF-8 HTML Пустое тело сообщения';
        $this->Mail->CharSet = 'UTF-8';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>HTML email test</title>
    </head>
    <body>
        <p>Chinese text: 郵件內容為空</p>
        <p>Russian text: Пустое тело сообщения</p>
        <p>Armenian text: Հաղորդագրությունը դատարկ է</p>
        <p>Czech text: Prázdné tělo zprávy</p>
    </body>
</html>
EOT;
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
    }

    /**
     * Send a message containing multilingual UTF-8 text with an embedded image.
     */
    public function testUtf8WithEmbeddedImage()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ': UTF-8 with embedded image';
        $this->Mail->CharSet = 'UTF-8';

        $this->Mail->Body = <<<'EOT'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>HTML email test</title>
    </head>
    <body>
        <p>Chinese text: 郵件內容為空</p>
        <p>Russian text: Пустое тело сообщения</p>
        <p>Armenian text: Հաղորդագրությունը դատարկ է</p>
        <p>Czech text: Prázdné tělo zprávy</p>
        Embedded Image: <img alt="phpmailer" src="cid:bäck">
    </body>
</html>
EOT;
        $this->Mail->addEmbeddedImage(
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer.png'),
            'bäck',
            'phpmailer.png',
            'base64',
            'image/png'
        );
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Send a message containing multilingual UTF-8 text.
     */
    public function testPlainUtf8()
    {
        $this->Mail->isHTML(false);
        $this->Mail->Subject .= ': UTF-8 plain text';
        $this->Mail->CharSet = 'UTF-8';

        $this->Mail->Body = <<<'EOT'
Chinese text: 郵件內容為空
Russian text: Пустое тело сообщения
Armenian text: Հաղորդագրությունը դատարկ է
Czech text: Prázdné tělo zprávy
EOT;
        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
    }

    /**
     * Test simple message builder and html2text converters.
     */
    public function testMsgHTML()
    {
        $message = file_get_contents(realpath(\PHPMAILER_INCLUDE_DIR . '/examples/contentsutf8.html'));
        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->Mail->Body = '';
        $this->Mail->AltBody = '';
        //Uses internal HTML to text conversion
        $this->Mail->msgHTML($message, realpath(\PHPMAILER_INCLUDE_DIR . '/examples'));
        $sub = $this->Mail->Subject . ': msgHTML';
        $this->Mail->Subject .= $sub;

        self::assertNotEmpty($this->Mail->Body, 'Body not set by msgHTML');
        self::assertNotEmpty($this->Mail->AltBody, 'AltBody not set by msgHTML');
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Again, using a custom HTML to text converter
        $this->Mail->AltBody = '';
        $this->Mail->msgHTML(
            $message,
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples'),
            static function ($html) {
                return strtoupper(strip_tags($html));
            }
        );
        $this->Mail->Subject = $sub . ' + custom html2text';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Test that local paths without a basedir are ignored
        $this->Mail->msgHTML('<img src="/etc/hostname">test');
        self::assertStringContainsString('src="/etc/hostname"', $this->Mail->Body);
        //Test that local paths with a basedir are not ignored
        $this->Mail->msgHTML('<img src="composer.json">test', realpath(\PHPMAILER_INCLUDE_DIR));
        self::assertStringNotContainsString('src="composer.json"', $this->Mail->Body);
        //Test that local paths with parent traversal are ignored
        $this->Mail->msgHTML('<img src="../composer.json">test', realpath(\PHPMAILER_INCLUDE_DIR));
        self::assertStringNotContainsString('src="composer.json"', $this->Mail->Body);
        //Test that existing embedded URLs are ignored
        $this->Mail->msgHTML('<img src="cid:5d41402abc4b2a76b9719d911017c592">test');
        self::assertStringContainsString('src="cid:5d41402abc4b2a76b9719d911017c592"', $this->Mail->Body);
        //Test that absolute URLs are ignored
        $this->Mail->msgHTML('<img src="https://github.com/PHPMailer/PHPMailer/blob/master/composer.json">test');
        self::assertStringContainsString(
            'src="https://github.com/PHPMailer/PHPMailer/blob/master/composer.json"',
            $this->Mail->Body
        );
        //Test that absolute URLs with anonymous/relative protocol are ignored
        //Note that such URLs will not work in email anyway because they have no protocol to be relative to
        $this->Mail->msgHTML('<img src="//github.com/PHPMailer/PHPMailer/blob/master/composer.json">test');
        self::assertStringContainsString(
            'src="//github.com/PHPMailer/PHPMailer/blob/master/composer.json"',
            $this->Mail->Body
        );
    }

    /**
     * An embedded attachment test.
     */
    public function testEmbeddedImage()
    {
        $this->Mail->msgHTML('<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>E-Mail Inline Image Test</title>
  </head>
  <body>
    <p><img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="></p>
  </body>
</html>');
        $this->Mail->preSend();
        self::assertStringContainsString(
            'Content-ID: <bb229a48bee31f5d54ca12dc9bd960c6@phpmailer.0>',
            $this->Mail->getSentMIMEMessage(),
            'Embedded image header encoding incorrect.'
        );
    }

    /**
     * An embedded attachment test.
     */
    public function testMultiEmbeddedImage()
    {
        $this->Mail->Body = 'Embedded Image: <img alt="phpmailer" src="' .
            'cid:my-attach">' .
            'Here is an image!</a>';
        $this->Mail->Subject .= ': Embedded Image + Attachment';
        $this->Mail->isHTML(true);

        if (
            !$this->Mail->addEmbeddedImage(
                realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer.png'),
                'my-attach',
                'phpmailer.png',
                'base64',
                'image/png'
            )
        ) {
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
     * Simple multipart/alternative test.
     */
    public function testAltBody()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->AltBody = 'Here is the plain text body of this message. ' .
            'It should be quite a few lines. It should be wrapped at ' .
            '40 characters.  Make sure that it is.';
        $this->Mail->WordWrap = 40;
        $this->addNote('This is a multipart/alternative email');
        $this->Mail->Subject .= ': AltBody + Word Wrap';

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple HTML and attachment test.
     */
    public function testAltBodyAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->AltBody = 'This is the text part of the email.';
        $this->Mail->Subject .= ': AltBody + Attachment';
        $this->Mail->isHTML(true);

        if (!$this->Mail->addAttachment(__FILE__, 'test_attach.txt')) {
            self::assertTrue(false, $this->Mail->ErrorInfo);

            return;
        }

        //Test using non-existent UNC path
        self::assertFalse($this->Mail->addAttachment('\\\\nowhere\\nothing'));

        $this->buildBody();
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending multiple messages with separate connections.
     */
    public function testMultipleSend()
    {
        $this->Mail->Body = 'Sending two messages without keepalive';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->Subject = $subject . ': SMTP 1';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->Subject = $subject . ': SMTP 2';
        $this->Mail->Sender = 'blah@example.com';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending an empty body.
     */
    public function testEmptyBody()
    {
        $this->buildBody();
        $this->Mail->Body = '';
        $this->Mail->Subject = $this->Mail->Subject . ': Empty Body';
        $this->Mail->isMail();
        $this->Mail->AllowEmpty = true;
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->AllowEmpty = false;
        self::assertFalse($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test keepalive (sending multiple messages in a single connection).
     */
    public function testSmtpKeepAlive()
    {
        $this->Mail->Body = 'SMTP keep-alive test.';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->SMTPKeepAlive = true;
        $this->Mail->Subject = $subject . ': SMTP keep-alive 1';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->Subject = $subject . ': SMTP keep-alive 2';
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->smtpClose();
    }

    /**
     * Test address escaping.
     */
    public function testAddressEscaping()
    {
        $this->Mail->addAttachment(
            realpath(\PHPMAILER_INCLUDE_DIR . '/examples/images/phpmailer_mini.png'),
            'phpmailer_mini.png'
        );
        self::assertTrue($this->Mail->attachmentExists());
    }

    /**
     * Test MIME structure assembly.
     */
    public function testMIMEStructure()
    {
        $this->Mail->Subject .= ': MIME structure';
        $this->Mail->Body = '<h3>MIME structure test.</h3>';
        $this->Mail->AltBody = 'MIME structure test.';
        $this->buildBody();
        $this->Mail->preSend();
        self::assertMatchesRegularExpression(
            "/Content-Transfer-Encoding: 8bit\r\n\r\n" .
            'This is a multi-part message in MIME format./',
            $this->Mail->getSentMIMEMessage(),
            'MIME structure broken'
        );
    }

    /**
     * Test base-64 encoding.
     */
    public function testBase64()
    {
        $this->Mail->Subject .= ': Base-64 encoding';
        $this->Mail->Encoding = 'base64';
        $this->buildBody();
        self::assertTrue($this->Mail->send(), 'Base64 encoding failed');
    }

    /**
     * S/MIME Signing tests (self-signed).
     *
     * @requires extension openssl
     */
    public function testSigning()
    {
        $this->Mail->Subject .= ': S/MIME signing';
        $this->Mail->Body = 'This message is S/MIME signed.';
        $this->buildBody();

        $dn = [
            'countryName' => 'UK',
            'stateOrProvinceName' => 'Here',
            'localityName' => 'There',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHPMailer',
            'commonName' => 'PHPMailer Test',
            'emailAddress' => 'phpmailer@example.com',
        ];
        $keyconfig = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $password = 'password';
        $certfile = 'certfile.pem';
        $keyfile = 'keyfile.pem';

        //Make a new key pair
        $pk = openssl_pkey_new($keyconfig);
        //Create a certificate signing request
        $csr = openssl_csr_new($dn, $pk);
        //Create a self-signed cert
        $cert = openssl_csr_sign($csr, null, $pk, 1);
        //Save the cert
        openssl_x509_export($cert, $certout);
        file_put_contents($certfile, $certout);
        //Save the key
        openssl_pkey_export($pk, $pkeyout, $password);
        file_put_contents($keyfile, $pkeyout);

        $this->Mail->sign(
            $certfile,
            $keyfile,
            $password
        );
        self::assertTrue($this->Mail->send(), 'S/MIME signing failed');

        $msg = $this->Mail->getSentMIMEMessage();
        self::assertStringNotContainsString("\r\n\r\nMIME-Version:", $msg, 'Incorrect MIME headers');
        unlink($certfile);
        unlink($keyfile);
    }

    /**
     * S/MIME Signing tests using a CA chain cert.
     * To test that a generated message is signed correctly, save the message in a file called `signed.eml`
     * and use openssl along with the certs generated by this script:
     * `openssl smime -verify -in signed.eml -signer certfile.pem -CAfile cacertfile.pem`.
     *
     * @requires extension openssl
     */
    public function testSigningWithCA()
    {
        $this->Mail->Subject .= ': S/MIME signing with CA';
        $this->Mail->Body = 'This message is S/MIME signed with an extra CA cert.';
        $this->buildBody();

        $certprops = [
            'countryName' => 'UK',
            'stateOrProvinceName' => 'Here',
            'localityName' => 'There',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHPMailer',
            'commonName' => 'PHPMailer Test',
            'emailAddress' => 'phpmailer@example.com',
        ];
        $cacertprops = [
            'countryName' => 'UK',
            'stateOrProvinceName' => 'Here',
            'localityName' => 'There',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHPMailer CA',
            'commonName' => 'PHPMailer Test CA',
            'emailAddress' => 'phpmailer@example.com',
        ];
        $keyconfig = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $password = 'password';
        $cacertfile = 'cacertfile.pem';
        $cakeyfile = 'cakeyfile.pem';
        $certfile = 'certfile.pem';
        $keyfile = 'keyfile.pem';

        //Create a CA cert
        //Make a new key pair
        $capk = openssl_pkey_new($keyconfig);
        //Create a certificate signing request
        $csr = openssl_csr_new($cacertprops, $capk);
        //Create a self-signed cert
        $cert = openssl_csr_sign($csr, null, $capk, 1);
        //Save the CA cert
        openssl_x509_export($cert, $certout);
        file_put_contents($cacertfile, $certout);
        //Save the CA key
        openssl_pkey_export($capk, $pkeyout, $password);
        file_put_contents($cakeyfile, $pkeyout);

        //Create a cert signed by our CA
        //Make a new key pair
        $pk = openssl_pkey_new($keyconfig);
        //Create a certificate signing request
        $csr = openssl_csr_new($certprops, $pk);
        //Create a self-signed cert
        $cacert = file_get_contents($cacertfile);
        $cert = openssl_csr_sign($csr, $cacert, $capk, 1);
        //Save the cert
        openssl_x509_export($cert, $certout);
        file_put_contents($certfile, $certout);
        //Save the key
        openssl_pkey_export($pk, $pkeyout, $password);
        file_put_contents($keyfile, $pkeyout);

        $this->Mail->sign(
            $certfile,
            $keyfile,
            $password,
            $cacertfile
        );
        self::assertTrue($this->Mail->send(), 'S/MIME signing with CA failed');
        unlink($cacertfile);
        unlink($cakeyfile);
        unlink($certfile);
        unlink($keyfile);
    }

    /**
     * Test line break reformatting.
     */
    public function testLineBreaks()
    {
        //May have been altered by earlier tests, can interfere with line break format
        $this->Mail->isSMTP();
        $this->Mail->preSend();

        //To see accurate results when using postfix, set `sendmail_fix_line_endings = never` in main.cf
        $this->Mail->Subject = 'PHPMailer DOS line breaks';
        $this->Mail->Body = "This message\r\ncontains\r\nDOS-format\r\nCRLF line breaks.";
        self::assertTrue($this->Mail->send());

        $this->Mail->Subject = 'PHPMailer UNIX line breaks';
        $this->Mail->Body = "This message\ncontains\nUNIX-format\nLF line breaks.";
        self::assertTrue($this->Mail->send());

        $this->Mail->Encoding = 'quoted-printable';
        $this->Mail->Subject = 'PHPMailer DOS line breaks, QP';
        $this->Mail->Body = "This message\r\ncontains\r\nDOS-format\r\nCRLF line breaks.";
        self::assertTrue($this->Mail->send());

        $this->Mail->Subject = 'PHPMailer UNIX line breaks, QP';
        $this->Mail->Body = "This message\ncontains\nUNIX-format\nLF line breaks.";
        self::assertTrue($this->Mail->send());
    }

    /**
     * Miscellaneous calls to improve test coverage and some small tests.
     */
    public function testMiscellaneous()
    {
        $this->Mail->clearAttachments();
        $this->Mail->isHTML(false);
        $this->Mail->isSMTP();
        $this->Mail->isMail();
        $this->Mail->isSendmail();
        $this->Mail->isQmail();
        $this->Mail->Sender = '';
        $this->Mail->createHeader();
    }

    public function testBadSMTP()
    {
        $this->Mail->smtpConnect();
        $smtp = $this->Mail->getSMTPInstance();
        self::assertFalse($smtp->mail("somewhere\nbad"), 'Bad SMTP command containing breaks accepted');
    }

    /**
     * Tests setting and retrieving ConfirmReadingTo address, also known as "read receipt" address.
     */
    public function testConfirmReadingTo()
    {
        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->buildBody();

        $this->Mail->ConfirmReadingTo = 'test@example..com';  //Invalid address
        self::assertFalse($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->ConfirmReadingTo = ' test@example.com';  //Extra space to trim
        self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        self::assertSame(
            'test@example.com',
            $this->Mail->ConfirmReadingTo,
            'Unexpected read receipt address'
        );

        $letter = html_entity_decode('&ccedil;', ENT_COMPAT, PHPMailer::CHARSET_UTF8);
        $this->Mail->ConfirmReadingTo = 'test@fran' . $letter . 'ois.ch';  //Address with IDN
        if (PHPMailer::idnSupported()) {
            self::assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
            self::assertSame(
                'test@xn--franois-xxa.ch',
                $this->Mail->ConfirmReadingTo,
                'IDN address not converted to punycode'
            );
        } else {
            self::assertFalse($this->Mail->send(), $this->Mail->ErrorInfo);
        }
    }

    /**
     * Test SMTP host connections.
     * This test can take a long time, so run it last.
     *
     * @group slow
     */
    public function testSmtpConnect()
    {
        $this->Mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL; //Show connection-level errors
        self::assertTrue($this->Mail->smtpConnect(), 'SMTP single connect failed');
        $this->Mail->smtpClose();

        //$this->Mail->Host = 'localhost:12345;10.10.10.10:54321;' . $_REQUEST['mail_host'];
        //self::assertTrue($this->Mail->smtpConnect(), 'SMTP multi-connect failed');
        //$this->Mail->smtpClose();
        //$this->Mail->Host = '[::1]:' . $this->Mail->Port . ';' . $_REQUEST['mail_host'];
        //self::assertTrue($this->Mail->smtpConnect(), 'SMTP IPv6 literal multi-connect failed');
        //$this->Mail->smtpClose();

        //All these hosts are expected to fail
        //$this->Mail->Host = 'xyz://bogus:25;tls://[bogus]:25;ssl://localhost:12345;
        //tls://localhost:587;10.10.10.10:54321;localhost:12345;10.10.10.10'. $_REQUEST['mail_host'].' ';
        //self::assertFalse($this->Mail->smtpConnect());
        //$this->Mail->smtpClose();

        $this->Mail->Host = ' localhost:12345 ; ' . $_REQUEST['mail_host'] . ' ';
        self::assertTrue($this->Mail->smtpConnect(), 'SMTP hosts with stray spaces failed');
        $this->Mail->smtpClose();

        //Need to pick a harmless option so as not cause problems of its own! socket:bind doesn't work with Travis-CI
        $this->Mail->Host = $_REQUEST['mail_host'];
        self::assertTrue($this->Mail->smtpConnect(['ssl' => ['verify_depth' => 10]]));

        $this->Smtp = $this->Mail->getSMTPInstance();
        self::assertInstanceOf(\get_class($this->Smtp), $this->Mail->setSMTPInstance($this->Smtp));
        self::assertFalse($this->Smtp->startTLS(), 'SMTP connect with options failed');
        self::assertFalse($this->Mail->SMTPAuth);
        $this->Mail->smtpClose();
    }
}
