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
use PHPMailer\Test\TestCase;

/**
 * Test recipient address getting, setting and enqueuing functionality.
 */
final class AddRecipientsTest extends TestCase
{

    /**
     * Run before each test is started.
     */
    protected function set_up()
    {
        /*
         * Only set "From", "to", "cc" or "bcc" should be set from within the individual tests
         * in this class for the preSend() command to succeed.
         */
        $this->propertyChanges['From'] = 'unit_test@phpmailer.example.com';

        // Initialize the PHPMailer class.
        parent::set_up();
    }

    /**
     * Test addressing.
     */
    public function testAddressing()
    {
        self::assertFalse($this->Mail->addAddress(''), 'Empty address accepted');
        self::assertFalse($this->Mail->addAddress('', 'Nobody'), 'Empty address with name accepted');
        self::assertFalse($this->Mail->addAddress('a@example..com'), 'Invalid address accepted');
        self::assertTrue($this->Mail->addAddress('a@example.com'), 'Addressing failed');
        self::assertFalse($this->Mail->addAddress('a@example.com'), 'Duplicate addressing failed');
        self::assertTrue($this->Mail->addCC('b@example.com'), 'CC addressing failed');
        self::assertFalse($this->Mail->addCC('b@example.com'), 'CC duplicate addressing failed');
        self::assertFalse($this->Mail->addCC('a@example.com'), 'CC duplicate addressing failed (2)');
        self::assertTrue($this->Mail->addBCC('c@example.com'), 'BCC addressing failed');
        self::assertFalse($this->Mail->addBCC('c@example.com'), 'BCC duplicate addressing failed');
        self::assertFalse($this->Mail->addBCC('a@example.com'), 'BCC duplicate addressing failed (2)');
        $this->Mail->clearCCs();
        $this->Mail->clearBCCs();
    }

    /**
     * Test address escaping.
     */
    public function testAddressEscaping()
    {
        $this->Mail->Subject .= ': Address escaping';
        $this->Mail->clearAddresses();
        $this->Mail->addAddress('foo@example.com', 'Tim "The Book" O\'Reilly');
        $this->Mail->Body = 'Test correct escaping of quotes in addresses.';
        $this->buildBody();
        $this->Mail->preSend();
        $b = $this->Mail->getSentMIMEMessage();
        self::assertStringContainsString('To: "Tim \"The Book\" O\'Reilly" <foo@example.com>', $b);

        $this->Mail->Subject .= ': Address escaping invalid';
        $this->Mail->clearAddresses();
        $this->Mail->addAddress('foo@example.com', 'Tim "The Book" O\'Reilly');
        $this->Mail->addAddress('invalidaddressexample.com', 'invalidaddress');
        $this->Mail->Body = 'invalid address';
        $this->buildBody();
        $this->Mail->preSend();
        self::assertSame('Invalid address:  (to): invalidaddressexample.com', $this->Mail->ErrorInfo);
    }

    /**
     * Test BCC-only addressing.
     */
    public function testBCCAddressing()
    {
        $this->Mail->isSMTP();
        $this->Mail->Subject .= ': BCC-only addressing';
        $this->buildBody();
        $this->Mail->clearAllRecipients();
        $this->Mail->addAddress('foo@example.com', 'Foo');
        $this->Mail->preSend();
        $b = $this->Mail->getSentMIMEMessage();
        self::assertTrue($this->Mail->addBCC('a@example.com'), 'BCC addressing failed');
        self::assertStringContainsString('To: Foo <foo@example.com>', $b);
    }

    /**
     * Tests CharSet and Unicode -> ASCII conversions for addresses with IDN.
     */
    public function testConvertEncoding()
    {
        if (!PHPMailer::idnSupported()) {
            self::markTestSkipped('intl and/or mbstring extensions are not available');
        }

        $this->Mail->clearAllRecipients();

        //This file is UTF-8 encoded. Create a domain encoded in "iso-8859-1".
        $letter = html_entity_decode('&ccedil;', ENT_COMPAT, PHPMailer::CHARSET_ISO88591);
        $domain = '@' . 'fran' . $letter . 'ois.ch';
        $this->Mail->addAddress('test' . $domain);
        $this->Mail->addCC('test+cc' . $domain);

        //Queued addresses are not returned by get*Addresses() before send() call.
        self::assertEmpty($this->Mail->getToAddresses(), 'Bad "to" recipients');
        self::assertEmpty($this->Mail->getCcAddresses(), 'Bad "cc" recipients');

        $this->buildBody();
        self::assertTrue($this->Mail->preSend(), $this->Mail->ErrorInfo);

        //Addresses with IDN are returned by get*Addresses() after send() call.
        $domain = $this->Mail->punyencodeAddress($domain);
        self::assertSame(
            [['test' . $domain, '']],
            $this->Mail->getToAddresses(),
            'Bad "to" recipients'
        );
        self::assertSame(
            [['test+cc' . $domain, '']],
            $this->Mail->getCcAddresses(),
            'Bad "cc" recipients'
        );
    }

    /**
     * Tests removal of duplicate recipients and reply-tos.
     */
    public function testDuplicateIDNRemoved()
    {
        if (!PHPMailer::idnSupported()) {
            self::markTestSkipped('intl and/or mbstring extensions are not available');
        }

        $this->Mail->clearAllRecipients();

        $this->Mail->CharSet = PHPMailer::CHARSET_UTF8;

        self::assertTrue($this->Mail->addAddress('test@françois.ch'));
        self::assertFalse($this->Mail->addAddress('test@françois.ch'));
        self::assertTrue($this->Mail->addAddress('test@FRANÇOIS.CH'));
        self::assertFalse($this->Mail->addAddress('test@FRANÇOIS.CH'));
        self::assertTrue($this->Mail->addAddress('test@xn--franois-xxa.ch'));
        self::assertFalse($this->Mail->addAddress('test@xn--franois-xxa.ch'));
        self::assertFalse($this->Mail->addAddress('test@XN--FRANOIS-XXA.CH'));

        $this->buildBody();
        self::assertTrue($this->Mail->preSend(), $this->Mail->ErrorInfo);

        //There should be only one "To" address and one "Reply-To" address.
        self::assertCount(
            1,
            $this->Mail->getToAddresses(),
            'Bad count of "to" recipients'
        );
    }

    public function testGivenIdnAddress_addAddress_returns_true()
    {
        if (file_exists(\PHPMAILER_INCLUDE_DIR . '/test/fakefunctions.php') === false) {
            $this->markTestSkipped('/test/fakefunctions.php file not found');
        }

        include \PHPMAILER_INCLUDE_DIR . '/test/fakefunctions.php';
        $this->assertTrue($this->Mail->addAddress('test@françois.ch'));
    }

    public function testErroneousAddress_addAddress_returns_false()
    {
        $this->assertFalse($this->Mail->addAddress('mehome.com'));
    }
}
