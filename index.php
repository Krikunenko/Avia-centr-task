<?php

require_once('ApiClient/ApiClient.php');

try {
    $api = isset($_GET['date']) ? new ApiClient($_GET['date']) : new ApiClient();
    $api->call();
    if (isset($_GET['temperature_conversion'])) {
        $api->setTemperatureConversion($_GET['temperature_conversion']);
    }
    if (isset($_GET['type'])) {
        $api->setType($_GET['type']);
    }
    $api->exportToFile();
} catch (ApiClientException $e) {
    die($e->getMessage());
}

?>