<?php
namespace Ecompassaro\Consumidor\Compra\Pagamento\Paypal;

class Payment
{
  private $apiContext;
  private $callbacks;

  const PAYMENT_METHOD_PAYPAL = 'paypal';
  const PAYMENT_INTENT_SALE = 'sale';

  public function __construct($config, $debugMode = false)
  {
    $rootKey = 'live';

    if ($debugMode) {
      $rootKey = 'sandbox';
    }

    $this->callbacks = $config[$rootKey]['callback'];

    $this->apiContext = new ApiContext(
        new OAuthTokenCredential(
            $config[$rootKey]['client']['id'],
            $config[$rootKey]['client']['secret']
        )
    );

    if ($debugMode) {
      $this->apiContext->setConfig( [
          'mode' => 'sandbox',
          'cache.enabled' => true,
      ] );
    }
  }

  public function create(Compra $compra)
  {
    $create = new Create($compra, $this->apiContext, $this->callbacks);
    return $create->sync();
  }

  public function cancel($paymentId, $payerId)
  {
  }

  public function success($paymentId, $payerId)
  {
  }
}
