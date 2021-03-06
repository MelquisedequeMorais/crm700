<?php

class PedidosController extends Tokem_ControllerBase
{

    protected $_usuarios = null;
    protected $_carrinho = null;
    protected $_produtos = null;
    protected $_identity = null;

    public function init()
    {
        parent::init();        

        $this->_baseUrl = $url = Zend_Controller_Front::getInstance()->getBaseUrl();        
        $this->_usuarios = new Application_Model_Usuarios();
        $this->_produtos = new Application_Model_Produto();
        $this->_carrinho  = new Tokem_Carrinho();
        $this->_dbAdapter = Zend_Db_Table::getDefaultAdapter();

        $auth = Zend_Auth::getInstance();
        $identity = $auth->getIdentity();
        $this->_identity = $identity;

        $this->view->headLink()->appendStylesheet('/crm700/public/plugins/alertifyjs/css/alertify.min.css');
        $this->view->headScript()->appendFile($this->_baseUrl . '/plugins/alertifyjs/alertify.min.js');
        $this->view->headScript()->appendFile($this->_baseUrl . '/files_js/controllers/pedidos/grade.js');

    }

    public function indexAction()
    {
        $this->view->titulo = "Pedidos";
    }


    public function testeAction(){        

        
    }


    public function carrinhoAction()
    {
        $this->view->titulo = "Carrinho";
        $id = $this->getRequest()->getParam('id');
        $authNamespace = new Zend_Session_Namespace('Carrinho');    

        if($id){            
            unset($authNamespace->carrinho);
            $this->_redirect('/pedidos/carrinho');
            exit; 
        }

    }

    public function resultadoAction(){


        $dados = $this->getRequest()->getParams();
        $request = $this->getRequest();                

       if ($request->isPost()){
          $nome = $dados["pro_nome"];
          $list = $this->_produtos->getAllByName($nome);
        }else{
           $list = array(); 
        }
        
        $paginator = Zend_Paginator::factory($list);
        $paginator->setCurrentPageNumber($this->_getParam('page'));
        $paginator->setItemCountPerPage(200);
        $this->view->lista = $paginator;
        //$this->view->paginator = $paginator;


    }


    public function somarItemAction()
    {

        $request = $this->getRequest();        
        $dados = $this->getRequest()->getParams();

        if($request->isXmlHttpRequest() && $request->isPost()){
            $this->_carrinho->somarItem($dados["id"],$dados["numero"]);
            exit;    
        }
        
    }




    public function subtrairItemAction()
    {
        $request = $this->getRequest();        
        $dados = $this->getRequest()->getParams();

        if($request->isXmlHttpRequest() && $request->isPost()){
            $this->_carrinho->subtrairItem($dados["id"],$dados["numero"]);
            exit;    
        }
        
    }


    public function pagamentoAction()
    {


        $this->view->headLink()->appendStylesheet('/crm700/public/plugins/alertifyjs/css/alertify.min.css');
        $this->view->headScript()->appendFile($this->_baseUrl . '/plugins/alertifyjs/alertify.min.js');


        $this->view->headLink()->appendStylesheet('/crm700/public/plugins/form_validation/vendor/bootstrap/css/bootstrap.css');
        $this->view->headLink()->appendStylesheet('/crm700/public/plugins/form_validation/dist/css/formValidation.css');

        $this->view->headScript()->appendFile($this->_baseUrl . '/plugins/form_validation/vendor/bootstrap/js/bootstrap.min.js');
        $this->view->headScript()->appendFile($this->_baseUrl . '/plugins/form_validation/dist/js/formValidation.js');
        $this->view->headScript()->appendFile($this->_baseUrl . '/plugins/form_validation/dist/js/framework/bootstrap.js');
        $this->view->headScript()->appendFile($this->_baseUrl . '/plugins/bootstrapvalidator/src/js/language/pt_BR.js');

        $this->view->headScript()->appendFile($this->_baseUrl . '/plugins/vanilla-masker/vanilla-masker.js');
        $this->view->headScript()->appendFile('https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js');
        $this->view->headScript()->appendFile($this->_baseUrl . '/files_js/controllers/pagamento/pagamento.js');

        $request = $this->getRequest();        
        $dados = $this->getRequest()->getParams();

        if($request->isXmlHttpRequest() && $request->isPost()){
            $this->_carrinho->somarItem($dados["id"],$dados["numero"]);
            exit;    
        }


        $email = 'vendas@700gauss.com.br';
        $token = '24707C512FF041ED96B762FE807CA552';
        $url = 'https://ws.sandbox.pagseguro.uol.com.br/v2/sessions';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf('email=%s&token=%s', $email, $token));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $ret = curl_exec($ch);
        curl_close($ch);
        libxml_use_internal_errors(true);
        $valid = simplexml_load_string($ret) !== false;


        if ($valid){
            $pagSeguro = new Zend_Session_Namespace('PagSeguro');    
            unset($pagSeguro->tokem);
            $pagSeguro->tokem;
            $xml = simplexml_load_string($ret);            
            $this->view->tokem = $xml->id;    
        }
        
        //$this->view->headScript()->appendFile($this->_baseUrl . '/files_js/controllers/pagamento/pagseguro.js');
    }


    public function excluirItemAction()
    {

        $request = $this->getRequest();        
        $dados = $this->getRequest()->getParams();

        if($request->isXmlHttpRequest() && $request->isPost()){
           $return = $this->_carrinho->excluirItem($dados["id"],$dados["numero"]);

           if($return){
            $flashMessenger = $this->_helper->FlashMessenger;   
                      $flashMessenger->addMessage('
                          <div class="alert alert-success alert-dismissible" role="alert">
                          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          <strong>Sucesso</strong> - Item excluído!
                          </div>
            ');
             echo true;
             exit;         
           }
           else{
                        $flashMessenger = $this->_helper->FlashMessenger;   
                        $flashMessenger->addMessage('<div class="alert alert-danger alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <strong>ERRO</strong> - Ocorreu um erro inesperado! se persistir entre em contato com o suporte!
                        </div>');
           }
           echo "error";
           exit;
        }
                

    }

    public function gradeAction()
    {   
        
        $this->view->titulo = "Novo Pedido";

        $list = $this->_produtos->getAll();
        $paginator = Zend_Paginator::factory($list);
        $paginator->setCurrentPageNumber($this->_getParam('page'));
        $paginator->setItemCountPerPage(20);

        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination.phtml');

        $this->view->lista = $paginator;
        $this->view->paginator = $paginator;

        $request = $this->getRequest();
        $authNamespace = new Zend_Session_Namespace('Carrinho');

        if ($request->isPost()) {        
        $dados = $this->getRequest()->getParams();
        unset($dados["controller"]);
        unset($dados["action"]);
        unset($dados["module"]);

        if(empty($authNamespace->carrinho)){
            $return = $this->_carrinho->adicionar($dados);
            if($return=="empty_post"){
                  $flashMessenger = $this->_helper->FlashMessenger;   
                  $flashMessenger->addMessage('<div class="alert alert-danger alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <strong>ERRO</strong> - Você precisa escolher ao menos um item para adicionar!
                  </div>');
          
                $this->_helper->redirector('grade');     
            }
        }else{

            $return = $this->_carrinho->verificarAtualizar($dados);
            if($return=="empty_post"){
                  $flashMessenger = $this->_helper->FlashMessenger;   
                  $flashMessenger->addMessage('<div class="alert alert-danger alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <strong>ERRO</strong> - Você precisa escolher ao menos um item para adicionar!
                  </div>');
          
                $this->_helper->redirector('grade');     
            }
        }
        
        $this->view->sucesso = 1;

        }    
                
    }

}