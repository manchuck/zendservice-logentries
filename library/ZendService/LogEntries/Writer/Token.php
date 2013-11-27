<?php
 /**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendService\LogEntries\Writer;

use Zend\Log\Formatter\Simple as SimpleFormatter;
use Zend\Log\Writer\AbstractWriter;
use ZendService\LogEntries\Exception\RuntimeException;

/**
 * Class Token
 *
 * @package ZendService\LogEntries\Writer
 * @author  Chuck "MANCHUCK" Reeves <chuck@manchuck.com>
 */
class Token extends AbstractWriter
{
    const LOG_ENTRIES_ADDRESS     = 'tcp://api.logentries.com';
    const LOG_ENTRIES_TLS_ADDRESS = 'tls://api.logentries.com';
    const LOG_ENTRIES_PORT        = 10000;
    const LOG_ENTRIES_TLS_PORT    = 20000;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $logSeparator = "\n";

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $useTLS = false;

    /**
     * @var bool
     */
    protected $persistent = false;

    /**
     * @var int
     */
    protected $timeOut = 60;

    /**
     * @var int
     */
    protected $errorNum;

    /**
     * @var string
     */
    protected $errStr;

    /**
     * @param array|null|\Traversable $token
     * @param bool $useTLS
     * @param bool $persistent
     * @param int $timeout
     * @throws RuntimeException
     */
    public function __construct($token, $useTLS = false, $persistent = true, $timeout = 60)
    {
        if (empty($token)) {
            throw new RuntimeException('You cannot set a token writer with an empty token');
        }

        $this->token      = (string) $token;
        $this->useTLS     = (bool) $useTLS;
        $this->persistent = (bool) $persistent;
        $this->timeOut    = (float) $timeout;
        $this->formatter  = new SimpleFormatter('%timestamp% - %priorityName% - %message% %extra%');
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return is_resource($this->resource) && !feof($this->resource);
    }

    protected function connect()
    {
        // @codeCoverageIgnoreStart
        if ($this->isConnected()) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $port    = $this->useTLS ? self::LOG_ENTRIES_TLS_PORT : self::LOG_ENTRIES_PORT;
        $address = $this->useTLS ? self::LOG_ENTRIES_TLS_ADDRESS : self::LOG_ENTRIES_ADDRESS;

        if ($this->persistent) {
            $resource = $this->openPersistentConnection($address, $port);
        } else {
            $resource = $this->openConnection($address, $port);
        }

        // @codeCoverageIgnoreStart
        if (is_resource($resource) && !feof($resource)) {
            $this->resource = $resource;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param string $address
     * @param int $port
     * @return resource
     * @cordCoverageIgnore
     */
    protected function openConnection($address, $port)
    {
        return @fsockopen($address, $port, $this->errorNum, $this->errStr, $this->timeOut);
    }

    /**
     * @param string $address
     * @param int $port
     * @return resource
     * @codeCoverageIgnore
     */
    protected function openPersistentConnection($address, $port)
    {
        return @pfsockopen($address, $port, $this->errorNum, $this->errStr, $this->timeOut);
    }

    /**
     * @param $line
     * @codeCoverageIgnore
     */
    protected function writeToLogEntries($line)
    {
        if ($this->isConnected()) {
            fputs($this->resource, $line);
        }
    }

    /**
     * @param string|\Zend\Log\Formatter\FormatterInterface $formatter
     * @return $this|AbstractWriter
     * @codeCoverageIgnore
     */
    public function setFormatter($formatter)
    {
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function shutdown()
    {
        if (is_resource($this->resource) && !$this->persistent) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    /**
     * @param array $event log data event
     * @return void
     */
    protected function doWrite(array $event)
    {
        $this->connect();
        $line = $this->token . ' ' . $this->formatter->format($event) . $this->logSeparator;
        $this->writeToLogEntries($line);
    }
}
