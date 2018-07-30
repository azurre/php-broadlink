# BroadLink devices communications library

Based on https://github.com/nick7zmail/MajorDoMo-dev_broadlink

Supported devices:
 - BroadLink SP3S (0x947a) 

## Usage

### Find devices in local network
```php
$loader = require_once __DIR__ . '/vendor/autoload.php';
echo '<pre>';
$devices = \Azurre\Component\SmartDevice\Broadlink\Transport::discover();
print_r($devices);
```

Output
```
Array
(
    [0] => Array
        (
            [ip] => 192.168.0.190
            [mac] => 17:5e:be:34:ec:14
            [type] => 38010
            [name] => SP3S(16A)
        )

)
```

## Get authozitation data

```php
$loader = require_once __DIR__ . '/vendor/autoload.php';
echo '<pre>';
$device = new \Azurre\Component\SmartDevice\Broadlink\Device\SP3S;
$response = $device
    ->setHost('192.168.0.190')
    ->setMac('17:5e:be:34:ec:14')
    ->auth();

print_r($response);
$storage->saveAuthData('mydevice', $response); // Save auth data
```

Output
```
Array
(
    [id] => Array
        (
            [0] => 1
            [1] => 0
            [2] => 0
            [3] => 0
        )

    [key] => Array
        (
            [0] => 25
            [1] => 243
            [2] => 107
            [3] => 167
            [4] => 53
            [5] => 201
            [6] => 71
            [7] => 251
            [8] => 17
            [9] => 63
            [10] => 37
            [11] => 245
            [12] => 195
            [13] => 89
            [14] => 177
            [15] => 55
        )

    [time] => 1532957735
)
```

## Get power state
```php
$loader = require_once __DIR__ . '/vendor/autoload.php';
echo '<pre>';
$device = new \Azurre\Component\SmartDevice\Broadlink\Device\SP3S;
list($id, $key, $time) = $storage->getAuthData('mydevice'); // Get auth data
$device
    ->setHost('192.168.0.190')
    ->setMac('17:5e:be:34:ec:14')
if (time() - $time >= \Azurre\Component\SmartDevice\Broadlink\Transport::AUTH_KEY_EXPIRE) {
    $device->auth();
    // Save auth data
} else {
    // Authorize using saved credentials
    $device->auth($id, $key);
}
print_r( $device->getPowerState() );
```

Output
```
Array
(
    [power_state] => 1
    [light_state] => 0
)
```

## Get current power
```php
$loader = require_once __DIR__ . '/vendor/autoload.php';
echo '<pre>';
$device = new \Azurre\Component\SmartDevice\Broadlink\Device\SP3S;
list($id, $key, $time) = $storage->getAuthData('mydevice'); // Get auth data
$device
    ->setHost('192.168.0.190')
    ->setMac('17:5e:be:34:ec:14')
if (time() - $time >= \Azurre\Component\SmartDevice\Broadlink\Transport::AUTH_KEY_EXPIRE) {
    $device->auth();
    // Save auth data
} else {
    // Authorize using saved credentials
    $device->auth($id, $key);
}
var_dump( $device->getCurrentPower() )
```

Output
```
float(268.32)
```

## Get current power
```php
$loader = require_once __DIR__ . '/vendor/autoload.php';
echo '<pre>';
$device = new \Azurre\Component\SmartDevice\Broadlink\Device\SP3S;
list($id, $key, $time) = $storage->getAuthData('mydevice'); // Get auth data
$device
    ->setHost('192.168.0.190')
    ->setMac('17:5e:be:34:ec:14')
if (time() - $time >= \Azurre\Component\SmartDevice\Broadlink\Transport::AUTH_KEY_EXPIRE) {
    $device->auth();
    // Save auth data
} else {
    // Authorize using saved credentials
    $device->auth($id, $key);
}
var_dump( $device->getCurrentPower() )
```

## Set power state
```php
$loader = require_once __DIR__ . '/vendor/autoload.php';
echo '<pre>';
$device = new \Azurre\Component\SmartDevice\Broadlink\Device\SP3S;
list($id, $key, $time) = $storage->getAuthData('mydevice'); // Get auth data
$device
    ->setHost('192.168.0.190')
    ->setMac('17:5e:be:34:ec:14')
if (time() - $time >= \Azurre\Component\SmartDevice\Broadlink\Transport::AUTH_KEY_EXPIRE) {
    $device->auth();
    // Save auth data
} else {
    // Authorize using saved credentials
    $device->auth($id, $key);
}
$turnOn = true;
$device->setPowerState($turnOn);
```


