<?php
namespace Ecompassaro\Consumidor\Compra\Pagamento;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Zend\Hydrator\ArraySerializable;
use Ecompassaro\Pagamento\Pagamento;
use Ecompassaro\Pagamento\Manager as PagamentoManager;
use Ecompassaro\Compra\Compra;

/**
 * Listener para eventos de pagamentos
 */
class Events implements ListenerAggregateInterface
{

    protected $listeners = array();
    protected $pagamentoManager;
    protected $eventManager;

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
      $this->eventManager = $events;
      $this->listeners[] = $events->attach(Compra::STATUS_FINALIZADA, array($this, 'registrarPagamento'));
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
        $compra = current($e->getParams());
        if ($compra instanceof Compra) {
            $pagamento = new Pagamento();
            $pagamento->setValor($compra->getPreco());
            $pagamento->setAutenticacao($compra->getAutenticacao());
            $this->pagamentoManager->salvar($pagamento);
        }
    }
}
