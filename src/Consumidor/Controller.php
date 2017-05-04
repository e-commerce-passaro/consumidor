<?php

namespace Ecompassaro\Consumidor;

use Ecompassaro\Acesso\Controller as AcessoController;
use Ecompassaro\Acesso\ViewModel as AcessoViewModel;
use Ecompassaro\Notificacao\FlashMessagesContainerTrait;
use Ecompassaro\Consumidor\Compra\ViewModel as CompraViewModel;

class Controller extends AcessoController
{
    use FlashMessagesContainerTrait;

    protected $resource = 'comprar';
    protected $compraViewModel;
    protected $autenticacaoId;

    public function __construct(AcessoViewModel $viewModel, CompraViewModel $compraViewModel, $autenticacaoId)
    {
        parent::__construct($viewModel);
        $this->compraViewModel = $compraViewModel;
        $this->autenticacaoId = $autenticacaoId;
    }

    /**
     * Ação de comprar um produto
     * Registra a compra e redireciona a página
     */
    public function executarCompraAction()
    {
        $params = array_merge_recursive(
            $this->params()->fromPost(),
            array(
                'autenticacao_id' => $this->autenticacaoId
            )
        );

        $routeRedirect = $this->params('routeRedirect');
        $this->getCompraViewModel()->setPreparedData($params);

        if ($this->getCompraViewModel()->getForm()->isValid()) {
            $this->getCompraViewModel()->finalizar($this->getCompraViewModel()->getForm()->getData());
            $this->setFlashMessagesFromNotificacoes($this->getCompraViewModel()->getNotificacoes());
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
