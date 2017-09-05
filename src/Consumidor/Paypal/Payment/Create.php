<?php
namespace Ecompassaro\Consumidor\Paypal\Payment;

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

  public function __construct(Compra $compra, ApiContext $apiContext)
  {
    $payer = new Payer();
    $payer->setPaymentMethod(Payment::PAYMENT_METHOD_PAYPAL);

    $item1 = new Item();
    $item1->setName($compra->getProduto()->getTitulo() . " (x{$compra->getQuantidade()})")
        ->setCurrency($compra->getMoeda())
        ->setQuantity($compra->getQuantidade())
        ->setSku($compra->getId())
        ->setPrice(7.5);
    $item2 = new Item();
    $item2->setName('Granola bars')
        ->setCurrency('USD')
        ->setQuantity(5)
        ->setSku("321321") // Similar to `item_number` in Classic API
        ->setPrice(2);
    $itemList = new ItemList();
    $itemList->setItems(array($item1, $item2));
    // ### Additional payment details
    // Use this optional field to set additional
    // payment information such as tax, shipping
    // charges etc.
    $details = new Details();
    $details->setShipping(1.2)
        ->setTax(1.3)
        ->setSubtotal(17.50);
    // ### Amount
    // Lets you specify a payment amount.
    // You can also specify additional details
    // such as shipping, tax.
    $amount = new Amount();
    $amount->setCurrency("USD")
        ->setTotal(20)
        ->setDetails($details);
    // ### Transaction
    // A transaction defines the contract of a
    // payment - what is the payment for and who
    // is fulfilling it.
    $transaction = new Transaction();
    $transaction->setAmount($amount)
        ->setItemList($itemList)
        ->setDescription("Payment description")
        ->setInvoiceNumber(uniqid());
    // ### Redirect urls
    // Set the urls that the buyer must be redirected to after
    // payment approval/ cancellation.
    $baseUrl = getBaseUrl();
    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl("$baseUrl/ExecutePayment.php?success=true")
        ->setCancelUrl("$baseUrl/ExecutePayment.php?success=false");
    // ### Payment
    // A Payment Resource; create one using
    // the above types and intent set to 'sale'
    $payment = new PaypalPayment();
    $payment->setIntent("sale")
        ->setPayer($payer)
        ->setRedirectUrls($redirectUrls)
        ->setTransactions(array($transaction));

  }

  public function sync()
  {
    $this->payment->create($this->apiContext);
    return $this->payment->getApprovalLink();
  }
}
