<?php
class GELFMessage {
    /**#@+
     *    Log levels according to syslog priority
     */
    const EMERGENCY = 0;
    const ALERT = 1;
    const CRITICAL = 2;
    const ERROR = 3;
    const WARNING = 4;
    const NOTICE = 5;
    const INFO = 6;
    const DEBUG = 7;
    /**#@-*/
    
    /**
     * @var string
     */
    private $version = null;

    /**
     * @var integer
     */
    private $timestamp = null;

    /**
     * @var string
     */
    private $shortMessage = null;

    /**
     * @var string
     */
    private $fullMessage = null;

    /**
     * @var string
     */
    private $facility = null;

    /**
     * @var string
     */
    private $host = null;

    /**
     * @var integer
     */
    private $level = null;

    /**
     * @var string
     */
    private $file = null;

    /**
     * @var integer
     */
    private $line = null;

    /**
     * @var array
     */
    private $data = array();

    /**
     * @param string $version
     * @return GELFMessage
     */
    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @param integer $timestamp
     * @return GELFMessage
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return integer
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * @param string $shortMessage
     * @return GELFMessage
     */
    public function setShortMessage($shortMessage) {
        $this->shortMessage = $shortMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getShortMessage() {
        return $this->shortMessage;
    }

    /**
     * @param string $fullMessage
     * @return GELFMessage
     */
    public function setFullMessage($fullMessage) {
        $this->fullMessage = $fullMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getFullMessage() {
        return $this->fullMessage;
    }

    /**
     * @param string $facility
     * @return GELFMessage
     */
    public function setFacility($facility) {
        $this->facility = $facility;
        return $this;
    }

    /**
     * @return string
     */
    public function getFacility() {
        return $this->facility;
    }

    /**
     * @param string $host
     * @return GELFMessage
     */
    public function setHost($host) {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param integer $level
     * @return GELFMessage
     */
    public function setLevel($level) {
        $this->level = $level;
        return $this;
    }

    /**
     * @return integer
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @param string $file
     * @return GELFMessage
     */
    public function setFile($file) {
        $this->file = $file;
        return $this;
    }

    /**
     * @return string
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @param integer $line
     * @return GELFMessage
     */
    public function setLine($line) {
        $this->line = $line;
        return $this;
    }

    /**
     * @return integer
     */
    public function getLine() {
        return $this->line;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return GELFMessage
     */
    public function setAdditional($key, $value) {
        $this->data["_" . trim($key)] = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAdditional($key) {
        return isset($this->data["_" . trim($key)]) ? $this->data[$key] : null;
    }

    /**
     * @return array
     */
    public function toArray() {
        $messageAsArray = array(
            'version' => $this->getVersion(),
            'timestamp' => $this->getTimestamp(),
            'short_message' => $this->getShortMessage(),
            'full_message' => $this->getFullMessage(),
            'facility' => $this->getFacility(),
            'host' => $this->getHost(),
            'level' => $this->getLevel(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        );

        foreach($this->data as $key => $value) {
            $messageAsArray[$key] = $value;
        }

        return $messageAsArray;
    }
}