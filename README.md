# MMPay PHP SDK

A professional, comprehensive PHP client library for integrating with the MMPay Payment Gateway. This SDK mimics the official Node.js SDK structure, providing robust utilities for payment creation, handshake authentication, and secure webhook verification.

## 📦 1. Installation

Requires PHP 7.4 or higher.

Install the package via Composer:

```bash
composer require myanmyanpay/mmpay-php-sdk
```

---


## 🚀 2. Configuration

To start, initialize the SDK with your Merchant credentials found in the MMPay Dashboard.

**Implementation**

```php
use MMPay\MMPay;

$options = [
    'appId'          => 'YOUR_APP_ID',
    'publishableKey' => 'YOUR_PUBLISHABLE_KEY',
    'secretKey'      => 'YOUR_SECRET_KEY',
    'apiBaseUrl'     => '[https://api.mmpay.com](https://api.mmpay.com)'
];

$mmpayx = new MMPay($options);
```

**Configuration Parameters**

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `appId` | `string` | **Yes** | Your unique Application ID. |
| `publishableKey` | `string` | **Yes** | Public key used for identification. |
| `secretKey` | `string` | **Yes** | Private key used for HMAC SHA256 signing. |
| `apiBaseUrl` | `string` | **Yes** | The base URL for the MMPay API. |


---


## 🛠 3. Create a Payment

`pay` This method automatically handles the required handshake and signature generation.

**Method Signature**
```php
$mmpayx->pay($payload);
```

**Implementation**
```php
try {
    $payload = [
        'orderId'       => 'ORD-SANDBOX-001',
        'amount'        => 5000,
        'currency'      => 'MMK',
        'callbackUrl'   => '[https://yoursite.com/webhook/mmpay](https://yoursite.com/webhook/mmpay)',
        'customMessage' => 'Thank you for shopping with us!', // Optional
        'items'         => [
            [
                'name'     => 'Premium Subscription',
                'amount'   => 5000,
                'quantity' => 1
            ]
        ]
    ];

    $response = $mmpayx->pay($payload);
    print_r($response);

} catch (Exception $e) {
    echo "Payment Failed: " . $e->getMessage();
}
```

**Request Body** (`payload` structure)

| Field | Type | Required | Description | Example |
| :--- | :--- | :--- | :--- | :--- |
| **`orderId`**         | `string` | **Yes**    | Your generated order ID for the order or system initiating the payment. | `"ORD-3983833"` |
| **`amount`**          | `number` | **Yes**    | The total transaction amount. | `1500.50` |
| **`callbackUrl`**     | `string` | No         | The URL where the payment gateway will send transaction status updates. | `"https://yourserver.com/webhook"` |
| **`currency`**        | `string` | No         | The currency code (e.g., `'MMK'`). | `"MMK"` |
| **`customMessage`**   | `string` | No         | Your Customization String |
| **`items`**           | `Array<Object>` | No  | List of items included in the purchase. | `[{name: "Hat", amount: 1000, quantity: 1}]` |

**Item Object**

| Field | Type | Description |
| :--- | :--- | :--- |
| **`name`** | `string` | The name of the item. |
| **`amount`** | `number` | The unit price of the item. |
| **`quantity`** | `number` | The number of units purchased. |


**Response Body** Code (`201`)

```json
{
  "orderId": "_trx_0012345",
  "status": "PENDING",
  "vendorQrRefId": "39233043003345",
  "transactionRefId": "39233043003345", // This is deprecated - transactionRefId will show only after payment is confirmed
  "amount": 2800,
  "currency": "MMK",
  "qr": "EMVco MMQR String => You_have_to_embed_as_qr_image_yourself"
}
```

---


## 🛠 4. Retrieve Payment

This method is used to retrieve a payment and MMQR related events.

**Method Signature**
```php
$mmpayx->get($payload);
```

**Implementation**
```php
try {
    $payload = [
        'orderId'     => 'ORD-LIVE-888'
    ];
    $response = $mmpayx->get($payload);
    if (isset($response['url'])) {
        header('Location: ' . $response['url']);
        exit;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

**Request Body** (`payload` structure)

| Field | Type | Required | Description | Example |
| :--- | :--- | :--- | :--- | :--- |
| **`orderId`**         | `string` | **Yes**    | Your generated order ID for the order or system initiating the payment. | `"ORD-3983833"` |

**Response Body** Code (`200`)

```json
{
  "orderId": "ORD-111111111",
  "appId": "MMP3883483",
  "amount": 1000,
  "vendor": "KBZPay",
  "method": "QR",
  "customMessage": "",
  "callbackUrl": "",
  "callbackUrlAt": "JSDateObject",
  "callbackUrlStatus": "SUCCESS",
  "status": "SUCCESS", //  'PENDING' | 'SUCCESS' | 'FAILED' | 'REFUNDED' | 'CANCELLED' | 'EXPIRED';
  "disbursementId": "289348734939",
  "disStatus": "SUCCESS",
  "condition": "TOUCHED", // TOUCHED | 'PRISTINE' | 'DIRTY' | 'EXPIRED'
  "createdAt": "JSDateObject",
  "transactionRefId": "939583046594",
  "vendorQrRefId": "48309449034",
  "qr": "EMVCo QR String::MMQR Standard",
}
```

---

## 🛠 5. Cancel Payment

This method is used to cancel a payment and all of its MMQR releated instances

**Method Signature**
```php
$mmpayx->cancel($payload);
```

**Implementation**
```php
try {
    $payload = [
        'orderId'     => 'ORD-LIVE-888'
    ];
    $response = $mmpayx->cancel($payload);
    if (isset($response['url'])) {
        header('Location: ' . $response['url']);
        exit;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

**Request Body** (`payload` structure)

| Field | Type | Required | Description | Example |
| :--- | :--- | :--- | :--- | :--- |
| **`orderId`**         | `string` | **Yes**    | Your generated order ID for the order or system initiating the payment. | `"ORD-3983833"` |


**Response Body** Code (`200`)

```json
{
  "amount": 1000,
  "orderId": "ORD-111111111",
  "status": "CANCELLED",
  "vendorQrRefId": "289348734939",
}
```


---


## 🔐 6. Handling Webhooks

To secure your webhook endpoint that receives callbacks from the MMPay server, use this event listener to handle the events.
The **listen** performs the mandatory Signature and Nonce verification and emits events

**Handling callbacks**

Incoming HTTP POST Parameters

Header

| Field Name | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| **Content-Type** | `string` | Yes | 'application/json' |
| **X-Mmpay-Signature** | `string` | Yes | '34834890vfgh9hnf94irfg_48932i4rt90349849' |
| **X-Mmpay-Nonce** | `string` | Yes | '94843943949349' |

Body

| Field Name    | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| **orderId**           | `string` | Yes | Unique identifier for the specific order. |
| **amount**            | `number` | Yes | The transaction amount. |
| **currency**          | `string` | Yes | The 3-letter currency code (e.g., MMK, USD). |
| **vendor**            | `string` | Yes | Identifier for the vendor initiating the request. |
| **method**            | `'QR', 'PIN', 'PWA', 'CARD'`  | Yes | Identifier for the method. |
| **status**            | `'PENDING','SUCCESS','FAILED','REFUNDED', 'EXPIRED', 'CANCELLED'` | Yes | Current status of the transaction. |
| **condition**         | `'PRESTINE', 'TOUCHED', 'EXPIRED', 'DIRTY'`  | Yes | Used QR Code scan again or not |
| **transactionRefId**  | `string` | Yes | The reference ID generated by the payment provider after success payment |
| **vendorQrRefId**     | `string` | Yes | The MMQR reference ID generated by the payment provider. |
| **callbackUrl**       | `string` | No | Optional URL to receive webhooks or updates. |
| **customMessage**     | `string` | No | User provided custom message |

**Example Implementation With Laravel**

In Laravel, you should use the `Request` object to fetch headers and the raw content.

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MMPay\MMPay;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $mmpayx = new MMPay([
            'appId' => config('services.mmpay.app_id'),
            'publishableKey' => config('services.mmpay.publish_key'),
            'secretKey' => config('services.mmpay.secret_key'),
            'apiBaseUrl' => 'https://api.myanmyanpay.com'
        ]);

        $mmpayx->onTxCreate(function ($tx) {
            \Log::info("Payment Created for order: " . $tx['orderId']);
            // Verify Source of truth here if you are using browser mmpayx showPaymentModal()
        });

        // Attach listeners
        $mmpayx->onTxSuccess(function ($tx) {
            // Update your database order status here
            \Log::info("Payment Successful for order: " . $tx['orderId']);
        });

        $mmpayx->onTxFail(function ($tx) {
            \Log::error("Payment Failed for order: " . $tx['orderId']);
        });

        $mmpayx->onTxCancel(function ($tx) {
            \Log::info("Payment Cancelled for order: " . $tx['orderId']);
        });

        $mmpayx->onTxExpire(function ($tx) {
            \Log::info("Payment Expired for order: " . $tx['orderId']);
        });

        $mmpayx->onHeartbeat(function ($tx) {
            \Log::info("Already Sent Event Coming in Again: " . $tx['orderId']);
        });

        // Get headers and raw body
        $nonce = $request->header('X-Mmpay-Nonce');
        $signature = $request->header('X-Mmpay-Signature');
        $payload = $request->getContent();

        // Listen triggers the events
        $mmpayx->listen($payload, $nonce, $signature);

        return response()->json(['received' => true], 200);
    }
}
```

---

## ⚠️ Error Handling

The mmpayx throws standard PHP `\Exception` when errors occur (e.g., network issues, API validation errors, or handshake failures).

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

---

## 7. Error Codes

##### Api Key Layer Authentication [SERVER SDK]
| Code | Description |
| :--- | :--- |
| **`KA0001`** | Bearer Token Not Included In Your Request |
| **`KA0002`** | API Key Not 'LIVE' |
| **`KA0003`** | Signature mismatch |
| **`KA0004`** | Internal Server Error ( Talk to our support immediately fot this ) |
| **`KA0005`** | IP Not whitelisted |
| **`429`** | Ratelimit hit only 1000 request / minute allowed |


##### JWT Layer Authentication [SERVER SDK]
| Code | Description |
| :--- | :--- |
| **`BA001`** | `Btoken` is nonce one time token is not included |
| **`BA002`** | `Btoken` one time nonce mismatch |
| **`BA000`** | Internal Server Error ( Talk to our support immediately fot this ) |
| **`429`**   | Ratelimit hit only 1000 request / minute allowed |


### Response Codes

| Code | Status | Description |
| :--- | :--- | :--- |
| **`201`** | Created | Transaction initiated successfully. Response contains QR code URL/details. |
| **`401`** | Unauthorized | Invalid or missing Publishable Key. |
| **`400`** | Bad Request | Missing required body fields (validated by schema, if implemented). |
| **`503`** | Service Unavailable | Upstream payment API failed or is unreachable. |
| **`500`** | Internal Server Error | General server error during payment initiation. |


---

## 📄 License

MIT License.