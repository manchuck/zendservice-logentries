<?php
 /**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendServiceTest\LogEntries\Writer;

use \PHPUnit_Framework_TestCase;
use Zend\Log\Logger;
use ZendService\LogEntries\Exception\RuntimeException;
use ZendService\LogEntries\Writer\Token;

/**
 * Class TokenTest
 *
 * @package ZendServiceTest\LogEntries\Writer
 * @author  Chuck "MANCHUCK" Reeves <chuck@manchuck.com>
 */
class TokenTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $params
     * @return \PHPUnit_Framework_MockObject_MockObject|\ZendService\LogEntries\Writer\Token
     */
    public function getTokenMock(array $params = array())
    {
        return $this->getMock(
            '\ZendService\LogEntries\Writer\Token',
            array('openConnection', 'openPersistentConnection', 'writeToLogEntries'),
            $params
        );
    }

    public function testLogsWarningWithNonPersistentTCPConnection()
    {
        $leToken = uniqid();
        $token = $this->getTokenMock(array($leToken, false, false));
        $token->expects($this->once())
            ->method('openConnection')
            ->with(Token::LOG_ENTRIES_ADDRESS, Token::LOG_ENTRIES_PORT);

        $testValue = null;
        $token->expects($this->once())
            ->method('writeToLogEntries')
            ->will($this->returnCallback(function ($value) use ($leToken, &$testValue) {
                $testValue = $value;
            }));


        $this->assertFalse($token->isConnected());
        $logger = new Logger();
        $logger->addWriter($token);
        $logger->warn('Test');

        $this->assertRegExp('/' . $leToken . ' .* - WARN - Test/', $testValue);
    }

    public function testLogsWarningWithPersistentTCPConnection()
    {
        $leToken = uniqid();
        $token = $this->getTokenMock(array($leToken, false));
        $token->expects($this->once())
            ->method('openPersistentConnection')
            ->with(Token::LOG_ENTRIES_ADDRESS, Token::LOG_ENTRIES_PORT);

        $testValue = null;
        $token->expects($this->once())
            ->method('writeToLogEntries')
            ->will($this->returnCallback(function ($value) use ($leToken, &$testValue) {
                $testValue = $value;
            }));

        $this->assertFalse($token->isConnected());
        $logger = new Logger();
        $logger->addWriter($token);
        $logger->warn('Test');

        $this->assertRegExp('/' . $leToken . ' .* - WARN - Test/', $testValue);
    }

    public function testLogsWarningWithNonPersistentTLSConnection()
    {
        $leToken = uniqid();
        $token = $this->getTokenMock(array($leToken, true, false));
        $token->expects($this->once())
            ->method('openConnection')
            ->with(Token::LOG_ENTRIES_TLS_ADDRESS, Token::LOG_ENTRIES_TLS_PORT);

        $testValue = null;
        $token->expects($this->once())
            ->method('writeToLogEntries')
            ->will($this->returnCallback(function ($value) use ($leToken, &$testValue) {
                $testValue = $value;
            }));

        $this->assertFalse($token->isConnected());
        $logger = new Logger();
        $logger->addWriter($token);
        $logger->warn('Test');

        $this->assertRegExp('/' . $leToken . ' .* - WARN - Test/', $testValue);
    }

    public function testLogsWarningWithPersistentTLSConnection()
    {
        $leToken = uniqid();
        $token = $this->getTokenMock(array($leToken, true));
        $token->expects($this->once())
            ->method('openPersistentConnection')
            ->with(Token::LOG_ENTRIES_TLS_ADDRESS, Token::LOG_ENTRIES_TLS_PORT);

        $testValue = null;
        $token->expects($this->once())
            ->method('writeToLogEntries')
            ->will($this->returnCallback(function ($value) use ($leToken, &$testValue) {
                $testValue = $value;
            }));

        $this->assertFalse($token->isConnected());
        $logger = new Logger();
        $logger->addWriter($token);
        $logger->warn('Test');

        $this->assertRegExp('/' . $leToken . ' .* - WARN - Test/', $testValue);
    }

    public function testExceptionThrownWithEmptyToken()
    {
        try {
            $token = new Token('');
        } catch (RuntimeException $exception) {
            $this->assertSame('You cannot set a token writer with an empty token', $exception->getMessage());
            return;
        }

        $this->fail('Exception not thrown');
    }
}
