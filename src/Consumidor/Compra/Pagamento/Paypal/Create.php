<?php
namespace Ecompassaro\Consumidor\Compra\Pagamento\Paypal;

use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment as PaypalPayment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use Ecompassaro\Compra\Compra;

class Create implements Syncronizable
{
  private $payment;
  private $apiContext;

  const PAYMENT_METHOD_PAYPAL = 'paypal';

  public function __construct(Compra $compra, ApiContext $apiContext, $callbacks)
  {
    $payer = new Payer();
    $payer->setPaymentMethod(Payment::PAYMENT_METHOD_PAYPAL);

    $item = new Item();
    $item->setName($compra->getProduto()->getTitulo() . " (x{$compra->getQuantidade()})")
        ->setCurrency($compra->getMoeda())
        ->setQuantity($compra->getQuantidade())
        ->setSku($compra->getId())
        ->setPrice($compra->getPreco());

    $itemList = new ItemList();
    $itemList->setItems([$item]);
    $details = new Details();
    $details->setShipping(0) // TODO
        ->setTax(0) // TODO
        ->setSubtotal($compra->getPreco());

    $amount = new Amount();
    $amount->setCurrency($compra->getMoeda())
        ->setTotal($compra->getQuantidade())
        ->setDetails($details);

    $transaction = new Transaction();
    $transaction->setAmount($amount)
        ->setItemList($itemList)
        ->setDescription($compra->getProduto()->getTitulo() . " (x{$compra->getQuantidade()})")
        ->setInvoiceNumber($compra->getId());

    $baseUrl = getBaseUrl();
    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl($callback['success'])
        ->setCancelUrl($callback['cancel']);

    $this->payment = new PaypalPayment();
    $this->payment->setIntent(Payment::PAYMENT_INTENT_SALE)
        ->setPayer($payer)
        ->setRedirectUrls($redirectUrls)
        ->setTransactions(array($transaction));
  }

  public function sync()
  {
    $this->payment->create($this->apiContext);
    return $this->payment;
  }
}
