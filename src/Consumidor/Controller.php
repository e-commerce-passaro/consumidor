<?php

namespace Ecompassaro\Consumidor;

use Ecompassaro\Acesso\Controller as AcessoController;
use Ecompassaro\Acesso\ViewModel as AcessoViewModel;
use Ecompassaro\Notificacao\FlashMessagesContainerTrait;
use Ecompassaro\Consumidor\Compra\ViewModel as CompraViewModel;
use Zend\Session\SessionManager;

class Controller extends AcessoController
{
    use FlashMessagesContainerTrait;

    const TEMPLATE_COMPRAR_RASCUNHO = 'comprar/rascunho';//tela sincronizando com o paypal
    const TEMPLATE_COMPRAR_PENDENTE = 'comprar/pendente'; //redirecionando para o paypal
    const TEMPLATE_COMPRAR_CANCELADA = 'comprar/cancelada';//flash message de compra cancelada
    const TEMPLATE_COMPRAR_PAGAMENTO_PENDENTE = 'comprar/pagamento_pendente';//flash message de faltam o pagamento
    const TEMPLATE_COMPRAR_FINALIZADA = 'comprar/finalizada';//flash message de compra concluida
    const TEMPLATE_COMPRAR_ERRO = 'comprar/erro';//tela flash message de erro inesperado com tente novamente

    protected $resource = 'comprar';
    protected $compraViewModel;
    protected $autenticacaoId;
    protected $currentSessionId;

    public function __construct(AcessoViewModel $viewModel, CompraViewModel $compraViewModel, $autenticacaoId)
    {
        parent::__construct($viewModel);
        $this->compraViewModel = $compraViewModel;
        $this->autenticacaoId = $autenticacaoId;
        $sessionManager = $this->getServiceLocator()->get(SessionManager::class);
        $this->sessionId = $sessionManager->getId();
    }

    private function statusScreen(Compra $compra, $params = [])
    {
        $template = self::TEMPLATE_COMPRAR_ERRO;

        if ($compra->getStatus()->equals(Compra::STATUS_RASCUNHO)) {
            $this->eventManager->trigger(Compra::STATUS_RASCUNHO, $this, $compra);
            $template = self::TEMPLATE_COMPRAR_RASCUNHO;
        } elseif ($compra->getStatus()->equals(Compra::STATUS_PENDENTE)) {
            return $this->redirect()->toUrl($compra->getUrlPagamento());
        } elseif ($compra->getStatus()->equals(Compra::STATUS_CANCELADA)) {
            $template = self::TEMPLATE_COMPRAR_CANCELADA;
        } elseif ($compra->getStatus()->equals(Compra::STATUS_PAGAMENTO_PENDENTE)) {
            $template = self::TEMPLATE_COMPRAR_PAGAMENTO_PENDENTE;
        } elseif ($compra->getStatus()->equals(Compra::STATUS_FINALIZADA)) {
            $template = self::TEMPLATE_COMPRAR_FINALIZADA;
        }

        return $this->getCompraViewModel()->setTemplate($template);
    }

    /**
     * Ação de comprar um produto
     * Registra a compra e redireciona a página
     */
    public function executarCompraAction()
    {
        $postParams = $this->params()->fromPost();
        $params = array_merge_recursive(
            $postParams,
            array(
                'autenticacao_id' => $this->autenticacaoId,
                'temporary_id' => $this->sessionId . '_pro_' . $postParams['produto_id']
            )
        );

        $routeRedirect = $this->params('routeRedirect');
        $this->getCompraViewModel()->setPreparedData($params);

        if ($this->getCompraViewModel()->getForm()->isValid()) {
            $compra = $this->getCompraViewModel()->criar($this->getCompraViewModel()->getForm()->getData());
            $this->setFlashMessagesFromNotificacoes($this->getCompraViewModel()->getNotificacoes());

            return $this->statusScreen($compra, $params);
        } else {
            $this->setFlashMessagesFromNotificacoes($this->getCompraViewModel()->getForm()->getMessages());
            $routeRedirect = null;
        }

        if (!$routeRedirect) {
            return $this->redirect()->toRoute('site');
        }

        return $this->redirect()->toRoute($routeRedirect);
    }

    public function comprarAction()
    {
        return $this->getCompraViewModel()->setTemplate('comprar/index');
    }

    /**
     * @return CompraViewModel
     */
    private function getCompraViewModel()
    {
        return $this->compraViewModel;
    }
}
