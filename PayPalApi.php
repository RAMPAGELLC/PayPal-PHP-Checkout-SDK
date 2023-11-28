<?php
// Copyright (©) 2023 RAMPAGE Interactive
// PayPal API SDK, based on PayPal JS Checkout SDK server.js

class PayPalApi {
    private $base = "https://api-m.sandbox.paypal.com";
    private $paypalClientId;
    private $paypalClientSecret;

    public function __construct(string $PAYPAL_CLIENT_ID, string $PAYPAL_CLIENT_SECRET) {
        $this->paypalClientId = $PAYPAL_CLIENT_ID;
        $this->paypalClientSecret = $PAYPAL_CLIENT_SECRET;
    }

    private function generateAccessToken() {
        if (empty($this->paypalClientId) || empty($this->paypalClientSecret)) throw new Exception("MISSING_API_CREDENTIALS");
        
        $auth = base64_encode("{$this->paypalClientId}:{$this->paypalClientSecret}");
        $response = $this->fetch("{$this->base}/v1/oauth2/token", [
            "method" => "POST",
            "body" => "grant_type=client_credentials",
            "headers" => [
                "Authorization" => "Basic {$auth}",
            ],
        ]);

        $data = json_decode($response, true);
        return $data["access_token"];
    }

    private function createOrder($cart) {
        $payload = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $cart["price"],
                    ],
                ],
            ],
        ];

        $response = $this->fetch("{$this->base}/v2/checkout/orders", [
            "method" => "POST",
            "headers" => [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer {$this->generateAccessToken()}",
            ],
            "body" => json_encode($payload),
        ]);

        return $this->handleResponse($response);
    }

    private function captureOrder($orderID) {
        $response = $this->fetch("{$this->base}/v2/checkout/orders/{$orderID}/capture", [
            "method" => "POST",
            "headers" => [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer {$this->generateAccessToken()}",
            ],
        ]);

        return $this->handleResponse($response);
    }

    private function handleResponse($response) {
        try {
            $jsonResponse = json_decode($response, true);
            return [
                "jsonResponse" => $jsonResponse,
                "httpStatusCode" => http_response_code(),
            ];
        } catch (Exception $err) {
            $errorMessage = $response;
            throw new Exception($errorMessage);
        }
    }

    public function processOrder($cart) {
        try {
            if (empty($cart) || empty($cart["id"]) || empty($cart["price"])) throw new Exception("Failed to create order.");
            $result = $this->createOrder($cart);
            
            http_response_code($result["httpStatusCode"]);
            echo json_encode($result["jsonResponse"]);
        } catch (Exception $error) {
            echo json_encode(["error" => "Failed to create order."]);
        }
    }

    public function captureOrderById($orderID) {
        try {
            $result = $this->captureOrder($orderID);
            http_response_code($result["httpStatusCode"]);
            echo json_encode($result["jsonResponse"]);
        } catch (Exception $error) {
            echo json_encode(["error" => "Failed to capture order."]);
        }
    }

    private function fetch($url, $options) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options["method"]);
        
        if (isset($options["body"])) curl_setopt($ch, CURLOPT_POSTFIELDS, $options["body"]);
        if (isset($options["headers"])) {
            $headers = [];
            
            foreach ($options["headers"] as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) throw new Exception('Curl error: ' . curl_error($ch));

        curl_close($ch);
        return $response;
    }
}
