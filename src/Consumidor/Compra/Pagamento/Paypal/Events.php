<?php
namespace Ecompassaro\Consumidor\Compra\Pagamento\Paypal;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Ecompassaro\Compra\Compra;
use Ecompassaro\Compra\Manager as CompraManager;
use Ecompassaro\Consumidor\Compra\Pagamento\Paypal\Payment;

/**
 * Listener para eventos de pagamentos
 */
class Events implements ListenerAggregateInterface
{

    protected $listeners = array();
    protected $payment;
    protected $eventManager;
    protected $compraManager;

    /**
     * Injeta dependências
     * @param \Pagamento\PagamentoManager $pagamentoManager
     */
    public function __construct($paypalConfig, CompraManager $compraManager, $debugMode = false)
    {
      $this->payment = new Payment ($paypalConfig, $debugMode);
      $this->pagamentoManager = $pagamentoManager;
      $this->compraManager = $compraManager;
    }

    /**
     * @see \Zend\EventManager\ListenerAggregateInterface::attach()
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
      $this->eventManager = $events;
      $this->listeners[] = $events->attach(Compra::STATUS_RASCUNHO, array($this, 'createPayment'));
      $this->listeners[] = $events->attach(Compra::STATUS_EXECUTADA, array($this, 'executePayment'));
    }

    /**
     * @see \Zend\EventManager\ListenerAggregateInterface::detach()
     */
    public function detach(EventManagerInterface $events)
    {
    }

    /**
     * Registra o pagamento a partir dos dados passados pelo evento
     * @param EventInterface $e
     */
     public function createPayment(EventInterface $e)
     {
         try{
             $compra = $e->getParams();
             $this->payment->create($compra);
             $this->eventManager->trigger(Compra::STATUS_CRIADA, $this, $compra);
         } catch(\Exception $e) {
              throw $e;
         }
     }

     public function executePayment(EventInterface $e)
     {
       $params = $e->getParams();
       if(isset($params['external_id'])) {
           $compra = $this->compraManager->obterCompraExterno($params['external_id']);

           $get = new Get($compra);
           $paymnet = $get->sync();

           if ($payment->getStatus() == 'approved') {
              $this->eventManager->trigger(Compra::STATUS_ACEITA, $this, $compra);
           } elseif ($payment->getStatus() == 'failed') {
              $this->eventManager->trigger(Compra::STATUS_RECUSADA, $this, $compra);
            } elseif ($payment->getStatus() == 'created') {
              $this->eventManager->trigger(Compra::STATUS_RASCUNHO, $this, $compra);
            } elseif ($payment->getStatus() == 'partially_completed' || 'in_progress') {
              $this->eventManager->trigger(Compra::STATUS_PAGANDO, $this, $compra);
            }
       }
     }

     public function cancelPayment(EventInterface $e)
     {
       $compra = $e->getParams();
       $this->payment->create($compra);
       //TODO salvar campos do paypal na compra
       $this->eventManager->trigger(Compra::STATUS_CRIADA, $this, $compra);
     }
}
