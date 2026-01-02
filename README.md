# MMPay PHP SDK

A professional, comprehensive PHP client library for integrating with the MMPay Payment Gateway. This SDK mimics the official Node.js SDK structure, providing robust utilities for payment creation, handshake authentication, and secure webhook verification.

## 📦 Installation

Requires PHP 7.4 or higher.

Install the package via Composer:

```bash
composer require your-vendor-name/mmpay-php-sdk
```

## 🚀 Configuration

To start, initialize the SDK with your Merchant credentials found in the MMPay Dashboard.

```php
use MMPay\MMPay;

$options = [
    'appId'          => 'YOUR_APP_ID',
    'publishableKey' => 'YOUR_PUBLISHABLE_KEY',
    'secretKey'      => 'YOUR_SECRET_KEY',
    'apiBaseUrl'     => '[https://api.mmpay.com](https://api.mmpay.com)'
];

$sdk = new MMPay($options);
```

### Configuration Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `appId` | `string` | **Yes** | Your unique Application ID. |
| `publishableKey` | `string` | **Yes** | Public key used for identification. |
| `secretKey` | `string` | **Yes** | Private key used for HMAC SHA256 signing. |
| `apiBaseUrl` | `string` | **Yes** | The base URL for the MMPay API. |

---

## 🛠 Usage

### 1. Create a Payment (Sandbox)

Use `sandboxPay` for testing. This method automatically handles the required handshake and signature generation.

```php
try {
    $params = [
        'orderId'     => 'ORD-SANDBOX-001',
        'amount'      => 5000,
        'currency'    => 'MMK',
        'callbackUrl' => '[https://yoursite.com/webhook/mmpay](https://yoursite.com/webhook/mmpay)',
        'items'       => [
            [
                'name'     => 'Premium Subscription',
                'amount'   => 5000,
                'quantity' => 1
            ]
        ]
    ];

    $response = $sdk->sandboxPay($params);
    print_r($response);

} catch (Exception $e) {
    echo "Payment Failed: " . $e->getMessage();
}
```

#### Parameters: `sandboxPay`

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `orderId` | `string` | **Yes** | Unique identifier for this order. |
| `amount` | `number` | **Yes** | Total transaction amount. |
| `items` | `array` | **Yes** | Array of item objects (see structure below). |
| `currency` | `string` | No | Currency code (e.g., "MMK"). |
| `callbackUrl` | `string` | No | URL where the webhook result will be posted. |

#### Structure: `items` (Array of Objects)

| Key | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `name` | `string` | **Yes** | Name of the product or service. |
| `amount` | `number` | **Yes** | Cost per unit. |
| `quantity` | `integer`| **Yes** | Number of units. |

---

### 2. Create a Payment (Production)

For live transactions, switch to the `pay` method.

```php
try {
    $params = [
        'orderId'     => 'ORD-LIVE-888',
        'amount'      => 10000,
        'items'       => [
            ['name' => 'E-Commerce Item', 'amount' => 10000, 'quantity' => 1]
        ]
    ];

    $response = $sdk->pay($params);
    
    // Redirect user to the payment URL
    if (isset($response['url'])) {
        header('Location: ' . $response['url']);
        exit;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### Parameters: `pay`

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `orderId` | `string` | **Yes** | Unique identifier for this order. |
| `amount` | `number` | **Yes** | Total transaction amount. |
| `items` | `array` | **Yes** | Array of item objects. |
| `currency` | `string` | No | Currency code (e.g., "MMK"). |
| `callbackUrl` | `string` | No | URL where the webhook result will be posted. |

---

### 3. Verify Webhook (Callback)

Secure your application by verifying the cryptographic signature of incoming webhooks. This ensures the request actually came from MMPay.

```php
// 1. Capture the raw POST body (Required for signature check)
$payload = file_get_contents('php://input');

// 2. Capture Headers
$headers = getallheaders(); // Or $request->getHeaders() in Laravel/Symfony
$nonce = $headers['X-Mmpay-Nonce'] ?? '';
$signature = $headers['X-Mmpay-Signature'] ?? '';

// 3. Verify
try {
    $isValid = $sdk->verifyCb($payload, $nonce, $signature);

    if ($isValid) {
        // ✅ Signature matched. Process the order.
        $data = json_decode($payload, true);
        $status = $data['status']; 
        
        if ($status === 'SUCCESS') {
            // Mark order as paid in DB
        }
        
        http_response_code(200);
        echo "OK";
    } else {
        // ❌ Signature mismatch. Potential fraud.
        http_response_code(400);
        echo "Invalid Signature";
    }
} catch (Exception $e) {
    http_response_code(400);
    echo "Error: " . $e->getMessage();
}
```

#### Parameters: `verifyCb`

| Parameter | Type | Description |
| :--- | :--- | :--- |
| `payload` | `string` | The **raw, unmodified** JSON string body of the request. |
| `nonce` | `string` | Value of the `X-Mmpay-Nonce` header. |
| `expectedSignature` | `string` | Value of the `X-Mmpay-Signature` header. |

---

## ⚠️ Error Handling

The SDK throws standard PHP `\Exception` when errors occur (e.g., network issues, API validation errors, or handshake failures).

```php
try {
    $sdk->pay($params);
} catch (\Exception $e) {
    // Log the error for debugging
    error_log($e->getMessage());
    
    // Return a user-friendly message
    echo "We could not process your payment at this time.";
}
```

## 📄 License

MIT License.