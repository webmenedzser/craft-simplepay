<?php
/**
 * Logger
 *
 * @category SDK
 * @package  SimplePayV2_SDK
 * @author   SimplePay IT Support <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */

namespace webmenedzser\craftsimplepay\services\simplepay\Sdk\Traits;

use \Exception;

trait LoggerTrait
{

    /**
     * Prepare log content before write in into log
     *
     * @param array $log Optional content of log. Default is $this->logContent
     *
     * @return boolean
     */
    public function writeLog($log = [])
    {
        $write = true;
        if (count($log) == 0) {
            $log = $this->logContent;
        }

        $date = @date('Y-m-d H:i:s', time());
        $logFile = $this->config['logPath'] . '/' . @date('Ymd', time()) . '.log';

        try {
            if (!is_writable($this->config['logPath'])) {
                $write = false;
                throw new Exception('Folder is not writable: ' . $this->config['logPath']);
            }
            if (file_exists($logFile)) {
                if (!is_writable($logFile)) {
                    $write = false;
                    throw new Exception('File is not writable: ' . $logFile);
                }
            }
        } catch (Exception $e) {
            $this->logContent['logFile'] = $e->getMessage();
        }

        if ($write) {
            $flat = $this->getFlatArray($log);
            $logText = '';
            foreach ($flat as $key => $value) {
                $logText .= $this->logOrderRef . $this->logSeparator;
                $logText .= $this->logTransactionId . $this->logSeparator;
                $logText .= $this->currentInterface . $this->logSeparator;
                $logText .= $date . $this->logSeparator;
                $logText .= $key . $this->logSeparator;
                $logText .= $this->contentFilter($key, $value) . "\n";
            }
            $this->logToFile($logFile, $logText);
            unset($log, $flat, $logText);
            return true;
        }
        return false;
    }

    /**
     * Remove card data from log content
     *
     * @param string $key   Log data key
     * @param string $value Log data value
     *
     * @return string  $logValue New log value
     */
    protected function contentFilter($key = '', $value = '')
    {
        $logValue = $value;
        $filtered = '***';
        if (in_array($key, ['content', 'sendContent'])) {
            $contentData = $this->convertToArray(json_decode($value));
            if (isset($contentData['cardData'])) {
                foreach (array_keys($contentData['cardData']) as $dataKey) {
                    $contentData['cardData'][$dataKey] = $filtered;
                }
            }
            if (isset($contentData['cardSecret'])) {
                $contentData['cardSecret'] = $filtered;
            }
            $logValue = json_encode($contentData);
        }
        if (strpos($key, 'cardData') !== false) {
            $logValue = $filtered;
        }
        if ($key === 'cardSecret') {
            $logValue = $filtered;
        }
        return $logValue;
    }

    /**
     * Write log into file
     *
     * @param array $logFile Log file
     * @param array $logText Log content
     *
     * @return boolean
     */
    protected function logToFile($logFile = '', $logText = '')
    {
        try {
            if (!file_put_contents($logFile, $logText, FILE_APPEND | LOCK_EX)) {
                throw new Exception('Log write error');
            }
        } catch (Exception $e) {
            $this->logContent['logToFile'] = $e->getMessage();
        }
        unset($logFile, $logText);
    }

}
