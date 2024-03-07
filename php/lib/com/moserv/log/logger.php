<?php
# require_once('com/log4php/Logger.php');
require_once('org/apache/log4php/Logger.php');


class MyConfigurator implements LoggerConfigurator {
	
    public function configure(LoggerHierarchy $hierarchy, $input = null) {
	global $_SERVER;
 
        // Use a different layout for the next appender
        $layout = new LoggerLayoutTTCC();
        $layout->setContextPrinting(false);
        $layout->setDateFormat('%Y-%m-%d %H:%M:%S');
	$layout->setMicroSecondsPrinting(false);
        $layout->activateOptions();

        // Create an appender which logs to file
        $appFile = new LoggerAppenderRollingFile('foo');
	$appFile->setLayout($layout);

	$defaultLogFile = '/tmp/dummy.log';
	$home = dirname(dirname($_SERVER['DOCUMENT_ROOT']));
	$logDir = "{$home}/logs";

	$logfile = (is_dir($logDir))? "$logDir/{$_SERVER['SERVER_NAME']}.php.log": $defaultLogFile;

	$appFile->setFile($logfile);
        $appFile->setAppend(true);
        $appFile->setThreshold('all');
	$appFile->setMaxBackupIndex(20);
	$appFile->setMaxFileSize('5MB');
        $appFile->activateOptions();
        
        
        $root = $hierarchy->getRootLogger();
        $root->addAppender($appFile);
#        $root->addAppender($appEcho);
    }
}
#date_default_timezone_set('Asia/Bangkok');
$configuration = array(
    'foo' => 1#,
#    'bar' => 2
);
 
// Passing the configurator as string
Logger::configure($configuration, 'MyConfigurator');

Logger::$logger = Logger::getLogger('');
