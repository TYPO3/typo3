<?php
class GELFMessagePublisher {
    /**
     * @var integer
     */
    const CHUNK_SIZE_WAN = 1420;

    /**
     * @var integer
     */
    const CHUNK_SIZE_LAN = 8154;

    /**
     * @var integer
     */
    const GRAYLOG2_DEFAULT_PORT = 12201;

    /**
     * @var string
     */
    const GRAYLOG2_PROTOCOL_VERSION = '1.0';

    /**
     * @var string
     */
    protected $hostname = null;

    /**
     * @var integer
     */
    protected $port = null;

    /**
     * @var integer
     */
    protected $chunkSize = null;

    /**
     * Creates a new publisher that sends errors to a Graylog2 server via UDP
     *
     * @throws InvalidArgumentException
     * @param string $hostname
     * @param integer $port
     * @param integer $chunkSize
     */
    public function __construct($hostname, $port = self::GRAYLOG2_DEFAULT_PORT, $chunkSize = self::CHUNK_SIZE_WAN) {
        // Check whether the parameters are set correctly
        if(!$hostname) {
            throw new InvalidArgumentException('$hostname must be set');
        }

        if(!is_numeric($port)) {
            throw new InvalidArgumentException('$port must be an integer');
        }

        if(!is_numeric($chunkSize)) {
            throw new InvalidArgumentException('$chunkSize must be an integer');
        }

        $this->hostname = $hostname;
        $this->port = $port;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Publishes a GELFMessage, returns false if an error occured during write
     *
     * @throws UnexpectedValueException
     * @param unknown_type $message
     * @return boolean
     */
    public function publish(GELFMessage $message) {
        // Check if required message parameters are set
        if(!$message->getShortMessage() || !$message->getHost()) {
            throw new UnexpectedValueException(
                'Missing required data parameter: "version", "short_message" and "host" are required.'
            );
        }

        // Set Graylog protocol version
        $message->setVersion(self::GRAYLOG2_PROTOCOL_VERSION);

        // Encode the message as json string and compress it using gzip
        $preparedMessage = $this->getPreparedMessage($message);

        // Open a udp connection to graylog server
        $socket = $this->getSocketConnection();

        // Several udp writes are required to publish the message
        if($this->isMessageSizeGreaterChunkSize($preparedMessage)) {
            // A unique id which consists of the microtime and a random value
            $messageId = $this->getMessageId();

            // Split the message into chunks
            $messageChunks = $this->getMessageChunks($preparedMessage);
            $messageChunksCount = count($messageChunks);

            // Send chunks to graylog server
            foreach(array_values($messageChunks) as $messageChunkIndex => $messageChunk) {
                    $bytesWritten = $this->writeMessageChunkToSocket(
                    $socket,
                    $messageId,
                    $messageChunk,
                    $messageChunkIndex,
                    $messageChunksCount
                );

                if(false === $bytesWritten) {
                    // Abort due to write error
                    return false;
                }
            }
        } else {
            // A single write is enough to get the message published
            if(false === $this->writeMessageToSocket($socket, $preparedMessage)) {
                // Abort due to write error
                return false;
            }
        }

        // This increases stability a lot if messages are sent in a loop
        // A value of 20 means 0.02 ms
        usleep(20);

        // Message successful sent
        return true;
    }

    /**
     * @param GELFMessage $message
     * @return string
     */
    protected function getPreparedMessage(GELFMessage $message) {
        return gzcompress(json_encode($message->toArray()));
    }

    /**
     * @return resource
     */
    protected function getSocketConnection() {
        return stream_socket_client(sprintf('udp://%s:%d', gethostbyname($this->hostname), $this->port));
    }

    /**
     * @param string $preparedMessage
     * @return boolean
     */
    protected function isMessageSizeGreaterChunkSize($preparedMessage) {
        return (strlen($preparedMessage) > $this->chunkSize);
    }

    /**
     * @return float
     */
    protected function getMessageId() {
        return (float) (microtime(true) . mt_rand(0, 10000));
    }

    /**
     * @param string $preparedMessage
     * @return array
     */
    protected function getMessageChunks($preparedMessage) {
        return str_split($preparedMessage, $this->chunkSize);
    }

    /**
     * @param float $messageId
     * @param string $data
     * @param integer $sequence
     * @param integer $sequenceSize
     * @throws InvalidArgumentException
     * @return string
     */
    protected function prependChunkInformation($messageId, $data, $sequence, $sequenceSize) {
        if(!is_string($data) || $data === '') {
            throw new InvalidArgumentException('Data must be a string and not be empty.');
        }

        if(!is_integer($sequence) || !is_integer($sequenceSize)) {
            throw new InvalidArgumentException('Sequence number and size must be integer.');
        }

        if($sequenceSize <= 0) {
            throw new InvalidArgumentException('Sequence size must be greater than 0.');
        }

        if($sequence > $sequenceSize) {
            throw new InvalidArgumentException('Sequence size must be greater than sequence number.');
        }

        return pack('CC', 30, 15) . substr(md5($messageId, true), 0, 8) . pack('CC', $sequence, $sequenceSize) . $data;
    }

    /**
     * @param resource $socket
     * @param float $messageId
     * @param string $messageChunk
     * @param integer $messageChunkIndex
     * @param integer $messageChunksCount
     * @return integer|boolean
     */
    protected function writeMessageChunkToSocket($socket, $messageId, $messageChunk, $messageChunkIndex, $messageChunksCount) {
        return fwrite(
            $socket,
            $this->prependChunkInformation($messageId, $messageChunk, $messageChunkIndex, $messageChunksCount)
        );
    }

    /**
     * @param resource $socket
     * @param string $preparedMessage
     * @return integer|boolean
     */
    protected function writeMessageToSocket($socket, $preparedMessage) {
        return fwrite($socket, $preparedMessage);
    }
}