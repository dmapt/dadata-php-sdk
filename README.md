dadata-sdk (unofficial)
=========================

A PHP library for the DaData.ru REST API


[API documentation](https://dadata.ru/api/clean/)

## Installation ##
Update your composer.json file
``` json
    "require": {
        "dmapt/dadata-php-sdk":"*"
    }
```

## Example ##
``` php
$dirtyNames = [
    'иванов иван иванович',
    'ivanov ivan ivanovich',
    'иванов',
];

try {
	$dadataApi = new dmapt\DaData\Api('YOUR API TOKEN', 'YOUR API SECRET');

	$response = null;

	$cleanNames = $dadataApi->cleanName($dirtyNames, 'result', $response);

	var_dump($cleanNames);
} catch (DaDataException $e) {
	var_dump($e->getMessage());
}
```

## Support ##
email: <apt.dmitry@gmail.com>