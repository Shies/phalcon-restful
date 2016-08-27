<?php

namespace Engine;

use Phalcon\DI;
use Phalcon\Exception as PhalconException;

/**
 * Exception class.
 * @package   Engine
 */
class Exception
{
    /**
     * Create exception.
     *
     * @param string     $message  Exception message.
     * @param array      $args     Message arguments (for placeholders, sprintf).
     * @param int        $code     Exception code.
     * @param \Exception $previous Previous exception.
     */
    public function __construct($message = "", $args = [], $code = 0, \Exception $previous = null)
    {
        parent::__construct(vsprintf($message, $args), $code, $previous);
    }

    /**
     * Log exception.
     *
     * @param \Exception $e Exception object.
     *
     * @return string
     */
    public static function logException(\Exception $e)
    {
        return self::logError(
            'Exception',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }

    /**
     * Log error.
     *
     * @param string $type Type name.
     * @param string $message Message text.
     * @param string $file File path.
     * @param string $line Line info.
     * @param string|null $trace Trace info.
     * @return string
     * @throws \Exception
     */
    public static function logError($type, $message, $file, $line, $trace = null)
    {
        $id = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
        $di = DI::getDefault();
        $template = "<%s> [%s] %s (File: %s Line: [%s])";
        $logMessage = sprintf($template, $id, $type, $message, $file, $line);

        if ($di->has('profiler')) {
            $profiler = $di->get('profiler');
            if ($profiler) {
                $profiler->addError($logMessage, $trace);
            }
        }

        if ($trace) {
            $logMessage .= $trace . PHP_EOL;
        } else {
            $logMessage .= PHP_EOL;
        }

        if ($di->has('logger')) {
            $logger = $di->get('logger');
            if ($logger) {
                $logger->error($logMessage);
            } else {
                throw new \Exception($logMessage);
            }
        } else {
            throw new \Exception($logMessage);
        }

        return $id;
    }
}
