<?php
namespace Ecompassaro\Consumidor\Compra;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;
use Ecompassaro\Compra\Compra;
use Ecompassaro\Compra\Manager as CompraManager;
use Ecompassaro\Consumidor\Compra\ViewModel as CompraViewModel;

/**
 * Listener para eventos de pagamentos
 */
class Events implements ListenerAggregateInterface
{

    protected $listeners = array();
    protected $compraManager;
    protected $eventManager;

    /**
     * Injeta dependências
     * @param \Pagamento\PagamentoManager $pagamentoManager
     */
    public function __construct(CompraManager $compraManager)
    {
        $this->compraManager = $compraManager;
    }

    /**
     * @see \Zend\EventManager\ListenerAggregateInterface::attach()
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
      $this->eventManager = $events;
      $this->listeners[] = $events->attach(CompraViewModel::EVENT_COMPRA_INICIADA, array($this, 'iniciar'));
      $this->listeners[] = $events->attach(CompraViewModel::EVENT_COMPRA_CRIADA, array($this, 'aguardar'));
    }

    /**
     * @see \Zend\EventManager\ListenerAggregateInterface::detach()
     */
    public function detach(EventManagerInterface $events)
    {
    }

    public function aguardar(EventInterface $e)
    {
      $compra = $e->getParams();
      $statusAguardando= $this->compraManager->getStatusManager()->obterStatusbyNome(Compra::STATUS_AGUARDANDO_PAGAMENTO);
      $compra->setStatus($statusAguardando);
      $compra = $this->compraManager->salvar($compra);
      $this->compraManager->preencherCompra($compra);
      $this->eventManager->trigger(CompraViewModel::EVENT_COMPRA_PENDENTE, $this, $compra);
    }

     public function iniciar(EventInterface $e)
     {
       $dados = $e->getParams();
       $statusIniciada= $this->compraManager->getStatusManager()->obterStatusbyNome(Compra::STATUS_INICIADA);
       $dados['status_id'] = $statusIniciada->getId();
       $compra = $this->hydrator->hydrate($dados, new Compra());
       $compra = $this->compraManager->salvar($compra);

       $this->compraManager->preencherCompra($compra);

       $this->eventManager->trigger(CompraViewModel::EVENT_COMPRA_RASCUNHO, $this, $compra);
     }
}
