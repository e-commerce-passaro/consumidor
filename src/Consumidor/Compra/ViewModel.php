<?php
namespace Ecompassaro\Consumidor\Compra;

use Zend\View\Model\ViewModel as ZendViewModel;
use Zend\Hydrator\HydrationInterface;
use Zend\EventManager\EventManagerInterface;
use Ecompassaro\Compra\Manager as CompraManager;
use Ecompassaro\Compra\Compra;
use Ecompassaro\Notificacao\NotificacoesContainerTrait;
use Ecompassaro\Notificacao\Notificacao;
// use Paypal\ExpressCheckout\ExpressCheckout;
// use Paypal\ExpressCheckout\PaymentRequest\PaymentRequest;
// use Paypal\ExpressCheckout\PaymentRequest\LPaymentRequest;

/**
 * Gerador da estrutura da página de anúncios
 */
class ViewModel extends ZendViewModel
{

    use NotificacoesContainerTrait;

    const MESSAGE_FINALIZADA_SUCCESS = 'A compra do item #%d foi efetuada com sucesso!';

    const MESSAGE_INTERNAL_ERROR = 'A compra do item #%d não aconteceu!';

    //nada aconteceu (acionado pela compra) [trigada pelo evento de salvamento da compra]
    const EVENT_COMPRA_INICIADA = 'compra.iniciada'; // chamada pelo criar

    //salva no banco (acionada pelo evento da compra de salvamento)
    const EVENT_COMPRA_RASCUNHO = 'compra.rascunho'; // chamada pelo criar

    //salva no paypal (acionada pela compra)
    const EVENT_COMPRA_CRIADA = 'compra.criada'; // chamada pelo criar

    //salva no banco (acionada pelo evento da compra de salvamento)
    const EVENT_COMPRA_PENDENTE = 'compra.pendente'; // chamada pelo criar

    //executada no paypal (acionada pela compra)
    const EVENT_COMPRA_EXECUTADA = 'compra.executada'; // chamada pelo finalizar e cancelar

    //salva no banco (acionada pelo evento da compra de salvamento)
    const EVENT_COMPRA_CANCELADA = 'compra.cancelada'; // chamada pelo cancelar

    //salva no banco (acionada pelo evento da compra de salvamento)
    const EVENT_COMPRA_FINALIZADA = 'compra.finalizada'; // chamada pelo cancelar

    private $compraManager;
    private $hydrator;
    private $form;
    private $eventManager;
    private $expressCheckout;

    /**
     * Injeta dependências
     *
     * @param \Produto\ProdutoManager $compraManager
     * @param CompraForm $form
     */
    public function __construct(CompraManager $compraManager, Form $form, HydrationInterface $hydrator, EventManagerInterface $eventManager, /*ExpressCheckout $expressCheckout,*/ $params = array())
    {
        $this->compraManager = $compraManager;
        $this->hydrator = $hydrator;
        $this->form = $form;
        $this->eventManager = $eventManager;

        $produtoId = false;
        extract($params);
        if ($produtoId) {
            $this->variables['formulario'] = $form->setProdutoId($produtoId)->prepare();
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

            $this->eventManager->trigger(self::EVENT_COMPRA_FINALIZADA, $this, $dados);

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

            $this->eventManager->trigger(self::EVENT_COMPRA_FINALIZADA, $this, $dados);

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
            $this->eventManager->trigger(self::EVENT_COMPRA_INICIADA, $this, $dados);
        } catch (\Exception $e) {
            die($e->getMessage().' '.$e->getTraceAsString());
            $this->addNotificacao(new Notificacao(Notificacao::TIPO_ERRO, self::MESSAGE_INTERNAL_ERROR, array(
                $compra->getProdutoId()
            )));
        }

        return true;
    }

    /**
     * @param array $data
     * @return CompraViewModel
     */
    public function setPreparedData($data)
    {
        $compra = $this->hydrator->hydrate($data, new Compra());
        $this->compraManager->preencherCompra($compra);
        $data['valor'] = $this->compraManager->caucularValorTotal($compra);
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
