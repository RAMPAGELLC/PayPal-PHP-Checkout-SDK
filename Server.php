<?php
// Copyright (©) 2023 RAMPAGE Interactive
// PayPal API SDK

$PayPalApi = new PayPalApi("CLIENT_ID", "CLIENT_SECRET", "JSON");

$Router->mount('/checkout', function () use ($Router) {
    $Router->mount('/orders', function () use ($Router) {
        $Router->all('/create', function () {
            global $PayPalApi;
            $PayPalApi->processOrder(json_decode(file_get_contents("php://input"), true));
        });

        $Router->all('/{orderID}/capture', function ($orderID) {
            global $PayPalApi;
            $PayPalApi->captureOrderById($orderID);
        });
    });
});
