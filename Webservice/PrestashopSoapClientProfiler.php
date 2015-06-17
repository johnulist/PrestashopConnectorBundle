<?php

namespace Pim\Bundle\PrestashopConnectorBundle\Webservice;

use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * Class PrestashopSoapClientProfiler.
 *
 */
class PrestashopSoapClientProfiler
{
    /** @staticvar string */
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /** @staticvar boolean */
    const IS_LOG_ACTIVE = false;

    /** @staticvar string */
    const LOG_FILE_NAME = 'soap_profile.log';

    /**
     * @param string $logDir
     */
    public function __construct($logDir)
    {
        $this->logDir = $logDir;
    }

    /**
     * Set ending time of the call and write log.
     *
     * @param StopwatchEvent $event
     * @param string         $resource
     */
    public function logCallDuration(StopwatchEvent $event, $resource)
    {
        if (static::IS_LOG_ACTIVE) {
            $filePath = $this->logDir.static::LOG_FILE_NAME;

            $duration = $event->getDuration() / 1000;
            $duration = number_format($duration, 3);

            $startTime = ($event->getOrigin() + $event->getStartTime()) / 1000;
            $callStart = date(static::DATE_FORMAT, (int) $startTime);

            $this->writeLog($filePath, $callStart, $resource, $duration);
        }
    }

    /**
     * Manage log file.
     *
     * @param string $filePath
     * @param string $callStart
     * @param string $resource
     * @param string $duration
     */
    protected function writeLog($filePath, $callStart, $resource, $duration)
    {
        $log = fopen($filePath, "a+");

        clearstatcache();
        $fileStats = lstat($filePath);
        if ($fileStats && 0 === $fileStats['size']) {
            fwrite($log, "datetime;soap_call_resource;duration (s)\n");
        }

        fwrite($log, sprintf("%s;%s;%s\n", $callStart, $resource, $duration));
        fclose($log);
    }
}
