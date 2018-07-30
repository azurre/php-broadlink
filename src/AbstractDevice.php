<?php
/**
 * @author Alex Milenin
 * @email admin@azrr.info
 * @date   28.07.2018
 */
namespace Azurre\Component\SmartDevice\Broadlink;

/**
 * Class AbstractDevice
 */
class AbstractDevice
{
    /**
     * @var \Azurre\Component\SmartDevice\Broadlink\Transport
     */
    protected $transport;

    /**
     * @var int Device type
     */
    protected $deviceType;

    /**
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->getTransport()->setHost($host);
        return $this;
    }

    /**
     * @param string $mac
     *
     * @return $this
     */
    public function setMac($mac)
    {
        $this->getTransport()->setMac($mac);
        return $this;
    }

    /**
     * @return \Azurre\Component\SmartDevice\Broadlink\Transport
     */
    public function getTransport()
    {
        if (!$this->transport) {
            $this->transport = new \Azurre\Component\SmartDevice\Broadlink\Transport();
        }

        return $this->transport;
    }
}
