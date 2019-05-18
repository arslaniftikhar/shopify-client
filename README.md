# Shopify PHP SDK
A simple Shopify PHP SDK for private apps to easily interact with the Shopify API.  

[Shopify API Documentation](https://docs.shopify.com/api) | [Packagist](https://packagist.org/packages/arslaniftikhar/shopify-client)

Package has following features:  

* ability to easily GET, PUT, POST and DELETE resources

## Setup/Installation
Uses [arslaniftikhar/curl](https://packagist.org/packages/arslaniftikhar/curl).
You need to include this library by running:  
`composer require arslaniftikhar/curl 1.0`

### Public App
First of all, you must have set up the public app. [View documentation](https://docs.shopify.com/api/introduction/getting-started).
You need an authorization URL.
```php
$shopify = new Shopify($shop, $APP_API_KEY, $APP_SECRET);

$client->installURL($permissions, $redirect_uri, $auto_redirect = true);
```

At this point, the user is taken to their store to authorize the application to use their information.  
If the user accepts, they are taken to the redirect URL.

```php
session_start();
$shopify = new Shopify($_GET['shop'], $APP_API_KEY, $APP_SECRET);

if ($token = $client->getAccessToken()) {
    // You can save the access token to database to make the api call in future.
  $_SESSION['shopify_access_token'] = $token;
  $_SESSION['shopify_shop_domain'] = $_GET['shop'];
  // Redirect to app's dashboard URL
  header("Location: dashboard.php");
}
else {
  die('Couldn't find the access token');
}

```

It's at this point, in **dashboard.php** you could starting doing API request by setting the `access_token`.

```php
session_start();
$shopify = new Shopify(, $APP_API_KEY, $APP_SECRET);
$shopify->setAccessToken($_SESSION['shopify_access_token']);

// you can get the resource by just passing the resource name.
$products = $shopify->get('products');
```
  
---

## Methods
### GET
Get resource information from the API.
```php
$shopify = new Shopify($SHOPIFY_SHOP_DOMAIN, $SHOPIFY_API_KEY, $SHOPIFY_SHARED_SECRET);
$result = $shopify->get('shop');
```
`$result` is a JSON decoded `array`:

Get product IDs by passing query params:
```php
$result = $shopify->get('products', ['query' => ['fields' => 'id']]);
foreach($result->products as $product) {
  print $product->id;
}
```

### POST
Create new content with a POST request.
```php
$data = ['product' => ['title' => 'my new product']];
$result = $shopify->post('products', $data);
```

### PUT
Update existing content with a given ID.
```php
$data = ['product' => ['title' => 'updated product name']];
$result = $shopify->put('products/' . $product_id, $data);
```

### DELETE
Easily delete resources with a given ID.
```php
$shopify->delete('products/' . $product_id);
```
