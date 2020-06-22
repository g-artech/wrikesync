<?php


namespace OCA\WrikeSync\AppInfo;

use OCP\ILogger;

class AppLogger
{

    public static function logInfo(ILogger $Logger, $message) {
        $Logger->info($message, array());
    }

    public static function logWarning(ILogger $Logger, $message) {
        $Logger->warning($message, array());
    }

    public static function logError(ILogger $Logger, $message) {
        $Logger->error($message, array());
    }

    public static function logCritical(ILogger $Logger, $message) {
        $Logger->critical($message, array());
    }

    public static function logDebug(ILogger $Logger, $message) {
        $Logger->debug($message, array());
    }

}