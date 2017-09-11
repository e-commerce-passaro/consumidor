<?php
namespace Ecompassaro\Consumidor\Compra;

use Zend\View\Model\ViewModel as ZendViewModel;
use Zend\Hydrator\HydrationInterface;
use Zend\EventManager\EventManagerInterface;
use Ecompassaro\Compra\Manager as CompraManager;
use Ecompassaro\Compra\Compra;
use Ecompassaro\Notificacao\NotificacoesContainerTrait;
use Ecompassaro\Notificacao\Notificacao;

/**
 * Gerador da estrutura da página de anúncios
 */
class ViewModel extends ZendViewModel
{

    use NotificacoesContainerTrait;

    const MESSAGE_FINALIZADA_SUCCESS = 'A compra do item #%d foi efetuada com sucesso!';

    const MESSAGE_INTERNAL_ERROR = 'A compra do item #%d não aconteceu!';

    private $compraManager;
    private $hydrator;
    private $form;
    private $eventManager;

    /**
     * Injeta dependências
     *
     * @param \Produto\ProdutoManager $compraManager
     * @param CompraForm $form
     */
    public function __construct(CompraManager $compraManager, Form $form, HydrationInterface $hydrator, EventManagerInterface $eventManager, $params = array())
    {
        $this->compraManager = $compraManager;
        $this->hydrator = $hydrator;
        $this->form = $form;
        $this->eventManager = $eventManager;

        $produtoId = false;
        extract($params);
        if ($produtoId && $autenticacao_id) {
            $temporaryId = $autenticacao_id . $produtoId;
            $this->variables['formulario'] = $form->setProdutoId($produtoId)->setTemporaryId($temporaryId)->prepare();
            $this->variables['produto'] = $compraManager->getProdutoManager()->getProduto($produtoId);
        }
    }

    /**
     * Finaliza uma compra a partir de um array
     *
     * @param array $dados
     *            a ser salvo
     * @return array contendo as mensagens de sucesso ou erro.
     */
    public function finalizar($dados)
    {
        try {
            $statusFinalizada = $this->compraManager->getStatusManager()->obterStatusbyNome(Compra::STATUS_FINALIZADA);
            $dados['status_id'] = $statusFinalizada->getId();
            $compra = $this->hydrator->hydrate($dados, new Compra());
            $compra = $this->compraManager->salvar($compra);
            $this->compraManager->preencherCompra($compra);
            $this->eventManager->trigger(Compra::STATUS_FINALIZADA, $this, $dados);
            $this->addNotificacao(new Notificacao(Notificacao::TIPO_SUCESSO, self::MESSAGE_FINALIZADA_SUCCESS, array(
                $compra->getProdutoId()
            )));

        } catch (\Exception $e) {
            die($e->getMessage().' '.$e->getTraceAsString());
            $this->addNotificacao(new Notificacao(Notificacao::TIPO_ERRO, self::MESSAGE_INTERNAL_ERROR, array(
                $compra->getProdutoId()
            )));
        }

        return true;
    }

    /**
     * Cancela uma compra a partir de um array
     *
     * @param array $dados
     *            a ser salvo
     * @return array contendo as mensagens de sucesso ou erro.
     */
    public function cancelar($dados)
    {
        try {
            $statusFinalizada = $this->compraManager->getStatusManager()->obterStatusbyNome(Compra::STATUS_FINALIZADA);
            $dados['status_id'] = $statusFinalizada->getId();
            $compra = $this->hydrator->hydrate($dados, new Compra());
            $compra = $this->compraManager->salvar($compra);

            $this->compraManager->preencherCompra($compra);

            $this->eventManager->trigger(Compra::STATUS_FINALIZADA, $this, $dados);

            $this->addNotificacao(new Notificacao(Notificacao::TIPO_SUCESSO, self::MESSAGE_FINALIZADA_SUCCESS, array(
                $compra->getProdutoId()
            )));
        } catch (\Exception $e) {
            die($e->getMessage().' '.$e->getTraceAsString());
            $this->addNotificacao(new Notificacao(Notificacao::TIPO_ERRO, self::MESSAGE_INTERNAL_ERROR, array(
                $compra->getProdutoId()
            )));
        }

        return true;
    }

    /**
     * Cria uma compra a partir de um array
     *
     * @param array $dados
     *            a ser salvo
     * @return array contendo as mensagens de sucesso ou erro.
     */
    public function criar($dados)
    {
        try {
            $this->eventManager->trigger(Compra::STATUS_INICIADA, $this, $dados);
            return $this->compraManager->obterCompraTemporary($dados['temporary_id']);
        } catch (\Exception $e) {
            die($e->getMessage().' '.$e->getTraceAsString());
            $this->addNotificacao(new Notificacao(Notificacao::TIPO_ERRO, self::MESSAGE_INTERNAL_ERROR, array(
                $compra->getProdutoId()
            )));
        }
    }

    /**
     * @param array $data
     * @return CompraViewModel
     */
    public function setPreparedData($data)
    {
        $compra = $this->hydrator->hydrate($data, new Compra());
        $this->compraManager->preencherCompra($compra);
        $data['valor'] = $compra->getPreco();
        $this->form->setData($data);
        return $this;
    }

    /**
     * @return CompraForm
     */
    public function getForm()
    {
        return $this->form;
    }
}
