<?php
namespace Ecompassaro\Consumidor\Pagamento;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Zend\Hydrator\ArraySerializable;
use Ecompassaro\Pagamento\Pagamento;
use Ecompassaro\Pagamento\Manager as PagamentoManager;
use Ecompassaro\Consumidor\Compra\ViewModel as CompraViewModel;

/**
 * Listener para eventos de pagamentos
 */
class Events implements ListenerAggregateInterface
{

    protected $listeners = array();
    protected $pagamentoManager;

    /**
     * Injeta dependências
     * @param \Pagamento\PagamentoManager $pagamentoManager
     */
    public function __construct(PagamentoManager $pagamentoManager)
    {
        $this->pagamentoManager = $pagamentoManager;
    }

    /**
     * @see \Zend\EventManager\ListenerAggregateInterface::attach()
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(CompraViewModel::EVENT_COMPRA_FINALIZADA, array($this, 'registrarPagamento'));
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
    public function registrarPagamento(EventInterface $e)
    {
        $pagamento = (new ArraySerializable())->hydrate($e->getParams(), new Pagamento());
        $this->pagamentoManager->salvar($pagamento);
    }
}
