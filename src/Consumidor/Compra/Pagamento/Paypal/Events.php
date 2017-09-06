<?php
namespace Ecompassaro\Consumidor\Compra\Pagamento\Paypal;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Ecompassaro\Consumidor\Compra\ViewModel as CompraViewModel;
use Ecompassaro\Consumidor\Compra\Pagamento\Paypal\Payment;

/**
 * Listener para eventos de pagamentos
 */
class Events implements ListenerAggregateInterface
{

    protected $listeners = array();
    protected $payment;
    protected $eventManager;

    /**
     * Injeta dependências
     * @param \Pagamento\PagamentoManager $pagamentoManager
     */
    public function __construct($paypalConfig, $debugMode = false)
    {
      $this->payment = new Payment ($paypalConfig, $debugMode);
      $this->pagamentoManager = $pagamentoManager;
    }

    /**
     * @see \Zend\EventManager\ListenerAggregateInterface::attach()
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
      $this->eventManager = $events;
      $this->listeners[] = $events->attach(CompraViewModel::EVENT_COMPRA_RASCUNHO, array($this, 'createPayment'));
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
      $compra = $e->getParams();
      $this->payment->create($compra);
      //TODO salvar campos do paypal na compra
      $this->eventManager->trigger(self::EVENT_COMPRA_CRIADA, $this, $compra);
    }
}
