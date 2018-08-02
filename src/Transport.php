<?php
/**
 * @author Alex Milenin
 * @email admin@azrr.info
 * @date 28.07.2018
 */
namespace Azurre\Component\SmartDevice\Broadlink;

/**
 * Class Transport
 */
class Transport
{
    /**
     * Auth command
     */
    const CMD_AUTH = 0x65;

    /**
     * Control command
     */
    const CMD_CONTROL = 0x6a;

    /**
     * Response code: no error
     */
    const NO_ERROR = 0;

    /**
     * Time to expire auth key
     */
    const AUTH_KEY_EXPIRE = 604800; // 7 Days

    protected $name;
    protected $host;
    protected $port = 80;
    protected $mac;
    protected $timeout = 10;

    protected $count;
    protected $key = [0x09, 0x76, 0x28, 0x34, 0x3f, 0xe9, 0x9e, 0x23, 0x76, 0x5c, 0x15, 0x13, 0xac, 0xcf, 0x8b, 0x02];
    protected $iv = [0x56, 0x2e, 0x17, 0x99, 0x6d, 0x09, 0x3d, 0x28, 0xdd, 0xb3, 0xba, 0x69, 0x5a, 0x2e, 0x6f, 0x58];
    protected $id = [0, 0, 0, 0];
    protected $encryptMethod = 'AES-128-CBC';

    /**
     * Transport constructor
     */
    public function __construct()
    {
        $this->count = mt_rand(0, 0xffff);
    }

    /**
     * @param string $localIpAddress
     * @param int    $port
     *
     * @return array
     */
    public static function discover($localIpAddress = null, $port = 80)
    {
        $devices = [];
        if (!$localIpAddress) {
            list($localIpAddress, $port) = static::getLocalIpAddress();
        }
        if (!$localIpAddress) {
            return $devices;
        }
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return $devices;
        }
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0]);
        socket_bind($socket, 0, 0);

        $address = explode('.', $localIpAddress);
        $packet = array_fill(0, 0x30, 0);
        $timezone = (int)date('Z') / -3600;
        $year = date('Y');
        $subYear = substr($year, 2);

        if ($timezone < 0) {
            $packet[0x08] = 0xff + $timezone - 1;
            $packet[0x09] = $packet[0x0a] = $packet[0x0b] = 0xff;
        } else {
            $packet[0x08] = $timezone;
            $packet[0x09] = $packet[0x0a] = $packet[0x0b] = 0;
        }
        $packet[0x0c] = $year & 0xff;
        $packet[0x0d] = $year >> 8;
        $packet[0x0e] = (int)date('i');
        $packet[0x0f] = (int)date('H');
        $packet[0x10] = (int)$subYear;
        $packet[0x11] = (int)date('N');
        $packet[0x12] = (int)date('d');
        $packet[0x13] = (int)date('m');
        $packet[0x18] = (int)$address[0];
        $packet[0x19] = (int)$address[1];
        $packet[0x1a] = (int)$address[2];
        $packet[0x1b] = (int)$address[3];
        $packet[0x1c] = $port & 0xff;
        $packet[0x1d] = $port >> 8;
        $packet[0x26] = 6;

        $checksum = 0xbeaf;
        $packetSize = count($packet);
        for ($i = 0; $i < $packetSize; $i++) {
            $checksum += $packet[$i];
        }
        $checksum &= 0xffff;

        $packet[0x20] = $checksum & 0xff;
        $packet[0x21] = $checksum >> 8;

        socket_sendto($socket, static::arrayToByte($packet), count($packet), 0, '255.255.255.255', 80);
        while (socket_recvfrom($socket, $response, 2048, 0, $from, $port)) {
            $responseData = static::byteToArray($response);
            $devType = hexdec(sprintf('%x%x', $responseData[0x35], $responseData[0x34]));
            $ipData = array_slice($responseData, 0x36, 4);
            $mac = static::arrayToMac(array_slice($responseData, 0x3a, 6));
            if (array_slice($responseData, 0, 8) !== [0x5a, 0xa5, 0xaa, 0x55, 0x5a, 0xa5, 0xaa, 0x55]) {
                $ipData = array_reverse($ipData);
            }
            $ip = implode('.', $ipData);
            $name = static::arrayToByte(array_slice($responseData, 0x40));
            $name = str_replace(["\0", "\2"], '', $name);
            $devices[] = [
                'ip'   => $ip,
                'mac'  => $mac,
                'type' => $devType,
                'name' => $name
            ];
        }

        socket_close($socket);

        return $devices;
    }

    /**
     * @return array
     */
    public function auth()
    {
        $payload = array_fill(0, 0x50, 0);
        $payload[0x04] = 0x31;
        $payload[0x05] = 0x31;
        $payload[0x06] = 0x31;
        $payload[0x07] = 0x31;
        $payload[0x08] = 0x31;
        $payload[0x09] = 0x31;
        $payload[0x0a] = 0x31;
        $payload[0x0b] = 0x31;
        $payload[0x0c] = 0x31;
        $payload[0x0d] = 0x31;
        $payload[0x0e] = 0x31;
        $payload[0x0f] = 0x31;
        $payload[0x10] = 0x31;
        $payload[0x11] = 0x31;
        $payload[0x12] = 0x31;
        $payload[0x1e] = 0x01;
        $payload[0x2d] = 0x01;
        $payload[0x30] = ord('T');
        $payload[0x31] = ord('e');
        $payload[0x32] = ord('s');
        $payload[0x33] = ord('t');
        $payload[0x34] = ord(' ');
        $payload[0x35] = ord(' ');
        $payload[0x36] = ord('1');

        $responseEncrypted = $this->sendRequest(static::CMD_AUTH, $payload);
        $response = static::byteToArray(
            $this->decrypt(
                $this->getKey(true),
                static::arrayToByte(array_slice($responseEncrypted, 0x38)),
                $this->getIv()
            )
        );
        $this->id = array_slice($response, 0x00, 4);
        $this->key = array_slice($response, 0x04, 16);

        return [
            'id'   => $this->id,
            'key'  => $this->key,
            'time' => time()
        ];
    }

    /**
     * @return array|null
     */
    public static function getLocalIpAddress()
    {
        $localIpAddress = $port = null;
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (socket_connect($socket, '8.8.8.8', 53)) { // connecting to a UDP address doesn't send packets
            socket_getsockname($socket, $localIpAddress, $port);
        }
        socket_close($socket);

        return [$localIpAddress, $port];
    }

    /**
     * @param int   $command
     * @param array $payload
     * @param bool  $decrypt
     *
     * @return array|false
     */
    public function sendRequest($command, $payload, $decrypt = false)
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return false;
        }
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_bind($socket, 0, 0);
        $this->count = ($this->count + 1) & 0xffff;
        $mac = [];
        $macData = explode(':', $this->getMac());
        foreach (array_reverse($macData) as $macByte) {
            $mac[] = is_string($macByte) ? hexdec($macByte) : $macByte;
        }
        $packet = array_fill(0, 0x38, 0);
        $packet[0x00] = 0x5a;
        $packet[0x01] = 0xa5;
        $packet[0x02] = 0xaa;
        $packet[0x03] = 0x55;
        $packet[0x04] = 0x5a;
        $packet[0x05] = 0xa5;
        $packet[0x06] = 0xaa;
        $packet[0x07] = 0x55;
        $packet[0x24] = 0x2a;
        $packet[0x25] = 0x27;
        $packet[0x26] = $command;
        $packet[0x28] = $this->count & 0xff;
        $packet[0x29] = $this->count >> 8;
        $packet[0x2a] = $mac[0];
        $packet[0x2b] = $mac[1];
        $packet[0x2c] = $mac[2];
        $packet[0x2d] = $mac[3];
        $packet[0x2e] = $mac[4];
        $packet[0x2f] = $mac[5];
        $packet[0x30] = $this->id[0];
        $packet[0x31] = $this->id[1];
        $packet[0x32] = $this->id[2];
        $packet[0x33] = $this->id[3];

        $checksum = 0xbeaf;
        foreach ($payload as $item) {
            $checksum += (int)$item;
            $checksum &= 0xffff;
        }

        $payloadEncrypted = static::byteToArray(
            $this->encrypt($this->getKey(true), static::arrayToByte($payload), $this->getIv())
        );

        $packet[0x34] = $checksum & 0xff;
        $packet[0x35] = $checksum >> 8;

        foreach ($payloadEncrypted as $item) {
            $packet[] = $item;
        }

        $checksum = 0xbeaf;
        foreach ($packet as $item) {
            $checksum += (int)$item;
            $checksum &= 0xffff;
        }

        $packet[0x20] = $checksum & 0xff;
        $packet[0x21] = $checksum >> 8;

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => 0]);
        socket_sendto($socket, static::arrayToByte($packet), count($packet), 0, $this->host, $this->port);
        socket_recvfrom($socket, $response, 2048, 0, $from, $port);
        socket_close($socket);

        $response = static::byteToArray($response);
        if ($decrypt) {
            if (!isset($response[0x23], $response[0x22])) {
                return false;
            }
            $err = hexdec(sprintf('%x%x', $response[0x23], $response[0x22]));
            if ($err !== static::NO_ERROR) {
                return false;
            }
            $payload = array_slice($response, 0x38);
            if (count($payload) > 0) {
                $response = static::byteToArray(
                    $this->decrypt($this->getKey(true), static::arrayToByte($payload), $this->getIv())
                );
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $id
     *
     * @return Transport
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param array $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param bool $byteMode
     *
     * @return array|string
     */
    public function getKey($byteMode = false)
    {
        if ($byteMode) {
            return implode(array_map('chr', $this->key));
        }

        return $this->key;
    }

    /**
     * @return string
     */
    public function getIv()
    {
        return implode(array_map('chr', $this->iv));
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return Transport
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return Transport
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * @param string $mac
     *
     * @return $this
     */
    public function setMac($mac)
    {
        $this->mac = $mac;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param string $data
     *
     * @return array
     */
    public static function byteToArray($data)
    {
        return array_merge(unpack('C*', $data));
    }

    /**
     * @param array $array
     *
     * @return string
     */
    public static function arrayToByte($array)
    {
        $byte = '';
        foreach ($array as $item) {
            $byte .= chr(is_string($item) ? hexdec($item) : $item);
        }

        return $byte;
    }

    /**
     * @param array $array
     *
     * @return string
     */
    public static function arrayToMac($array)
    {
        foreach ($array as $key => $byte) {
            $array[$key] = is_string($byte) ? $byte : dechex($byte);
        }

        return implode(':', $array);
    }

    /**
     * @param string $key
     * @param string $data
     * @param string $iv
     *
     * @return string
     */
    public function encrypt($key, $data, $iv)
    {
        $data = str_pad($data, ceil(strlen($data) / 16) * 16, chr(0), STR_PAD_RIGHT);

        return openssl_encrypt($data, $this->encryptMethod, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    }

    /**
     * @param string $key
     * @param string $data
     * @param string $iv
     *
     * @return string
     */
    public function decrypt($key, $data, $iv)
    {
        $options = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;

        return rtrim(openssl_decrypt($data, $this->encryptMethod, $key, $options, $iv), chr(0));
    }
}
