<?php
/**
 * @author Alex Milenin
 * @email admin@azrr.info
 * @date 29.07.2018
 */
namespace Azurre\Component\SmartDevice\Broadlink\Device;

/**
 * Class SP3
 */
class SP3S extends \Azurre\Component\SmartDevice\Broadlink\AbstractDevice
{
    /**
     * @var string
     */
    protected $model = 'SP3S';

    /**
     * @var int
     */
    protected $deviceType = 0x947a;

    /**
     * @param array|null $id
     * @param array|null $key
     *
     * @return array
     */
    public function auth($id = null, $key = null)
    {
        if ($id && $key) {
            $this
                ->getTransport()
                ->setId($id)
                ->setKey($key);

            return [
                'id'   => $this->getTransport()->getId(),
                'key'  => $this->getTransport()->getKey()
            ];
        }

        return $this->getTransport()->auth();
    }

    /**
     * @return array|bool
     */
    public function getPowerState()
    {
        $packet = array_fill(0, 16, 0);
        $packet[0] = 0x01;

        $response = $this->getTransport()->sendRequest(
            \Azurre\Component\SmartDevice\Broadlink\Transport::CMD_CONTROL,
            $packet,
            true
        );
        if ($response) {
            return [
                'power_state' => (bool)($response[0x4] & 0x01),
                'light_state' => (bool)($response[0x4] & 0x02) // SP3
            ];
        }

        return false;
    }

    /**
     * @param bool $enable
     */
    public function setPowerState($enable = true)
    {
        $packet = array_fill(0, 16, 0);
        $packet[0] = 0x02;
        $packet[4] = (int)(bool)$enable;
        $this->getTransport()->sendRequest(
            \Azurre\Component\SmartDevice\Broadlink\Transport::CMD_CONTROL,
            $packet
        );
    }

    /**
     * @return bool|float
     */
    public function getCurrentPower()
    {
        $packet = array_fill(0, 16, 0);
        $packet[0x00] = 0x08;
        $packet[0x02] = 0xFE;
        $packet[0x03] = 0x01;
        $packet[0x04] = 0x05;
        $packet[0x05] = 0x01;
        $packet[0x09] = 0x2D;
        $response = $this->getTransport()->sendRequest(
            \Azurre\Component\SmartDevice\Broadlink\Transport::CMD_CONTROL,
            $packet,
            true
        );
        if ($response) {
            return (dechex($response[0x7]) * 10000 + dechex($response[0x6]) * 100 + dechex($response[0x5])) / 100;
        }

        return false;
    }
}
