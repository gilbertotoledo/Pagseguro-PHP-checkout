<?php
header("access-control-allow-origin: https://pagseguro.uol.com.br");
header("Content-Type: text/html; charset=UTF-8",true);
date_default_timezone_set('America/Sao_Paulo');

require_once("PagSeguro.class.php");
$PagSeguro = new PagSeguro();
	
//EFETUAR PAGAMENTO	
$venda = array("codigo"=>"1",
			   "valor"=>100.00,
			   "descricao"=>"VENDA DE NONONONONONO",
			   "nome"=>"",
			   "email"=>"",
			   "telefone"=>"(XX) XXXX-XXXX",
			   "rua"=>"",
			   "numero"=>"",
			   "bairro"=>"",
			   "cidade"=>"",
			   "estado"=>"XX", //2 LETRAS MAIÚSCULAS
			   "cep"=>"XX.XXX-XXX",
			   "codigo_pagseguro"=>"");
			   
$PagSeguro->executeCheckout($venda,"http://SEUSITE/pedido/".$_GET['codigo']);

//----------------------------------------------------------------------------


//RECEBER RETORNO
if( isset($_GET['transaction_id']) ){
	$pagamento = $PagSeguro->getStatusByReference($_GET['codigo']);
	
	$pagamento->codigo_pagseguro = $_GET['transaction_id'];
	if($pagamento->status==3 || $pagamento->status==4){
		//ATUALIZAR DADOS DA VENDA, COMO DATA DO PAGAMENTO E STATUS DO PAGAMENTO
		
	}else{
		//ATUALIZAR NA BASE DE DADOS
	}
}

?>