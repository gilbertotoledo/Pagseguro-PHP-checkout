<?php
class PagSeguro{
	private $email         = "SEU EMAIL USADO NO CADASTRO DO PAGSEGURO";
	private $token_sandbox = "TOKEN DO SANDBOX";
	private $token_oficial = "TOKEN DO PAGSEGURO";
	private $url_retorno   = "http://SEUSITE/pagseguro/notificacao.php";
	
	//URL OFICIAL
	//COMENTE AS 4 LINHAS ABAIXO E DESCOMENTE AS URLS DA SANDBOX PARA REALIZAR TESTES
	private $url              = "https://ws.pagseguro.uol.com.br/v2/checkout/";
	private $url_redirect     = "https://pagseguro.uol.com.br/v2/checkout/payment.html?code=";
	private $url_notificacao  = 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/';
	private $url_transactions = 'https://ws.pagseguro.uol.com.br/v2/transactions/';

	//URL SANDBOX
	//DESCOMENTAR PARA REALIZAR TESTES
	/*
	private $url              = "https://ws.sandbox.pagseguro.uol.com.br/v2/checkout/";
	private $url_redirect     = "https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=";
	private $url_notificacao  = 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/notifications/';
	private $url_transactions = 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/';
	*/
	
	private $email_token = "";//NÃO MODIFICAR
	private $statusCode = array(0=>"Pendente",
								1=>"Aguardando pagamento",
								2=>"Em análise",
								3=>"Pago",
								4=>"Disponível",
								5=>"Em disputa",
								6=>"Devolvida",
								7=>"Cancelada");
		
	public function __construct(){
		$this->email_token = "?email=".$this->email."&token=".$this->token_oficial;
		$this->url .= $this->email_token;
	}
		
	private function generateUrl($dados,$retorno){
		//Configurações
		$data['email'] = $this->email;
		$data['token'] = $this->token_oficial;
		$data['currency'] = 'BRL';
		
		//Itens
		$data['itemId1'] = '0001';
		$data['itemDescription1'] = $dados['descricao'];
		$data['itemAmount1'] = number_format($dados['valor'],2,".","");
		$data['itemQuantity1'] = '1';
		$data['itemWeight1'] = '0';
		
		//Dados do pedido
		$data['reference'] = $dados['codigo'];		
			
		//Dados do comprador
		
		//Tratar telefone
		$telefone = implode("",explode("-",substr($dados['telefone'],5,strlen($dados['telefone']))));
		$ddd = substr($dados['telefone'],1,2);
		
		//Tratar CEP
		$cep = implode("",explode("-",$dados['cep']));
		$cep = implode("",explode(".",$cep));
		
		$data['senderName'] = $dados['nome'];
		$data['senderAreaCode'] = $ddd;
		$data['senderPhone'] = $telefone;
		$data['senderEmail'] = $dados['email'];
		$data['shippingType'] = '3';
		$data['shippingAddressStreet'] = $dados['rua'];
		$data['shippingAddressNumber'] = $dados['numero'];
		$data['shippingAddressComplement'] = " ";
		$data['shippingAddressDistrict'] = $dados['bairro'];
		$data['shippingAddressPostalCode'] = $cep;
		$data['shippingAddressCity'] = $dados['cidade'];
		$data['shippingAddressState'] = strtoupper($dados['estado']);
		$data['shippingAddressCountry'] = 'BRA';
		$data['redirectURL'] = $retorno;
			
		return http_build_query($data);
	}
	
	public function executeCheckout($dados,$retorno){
		
		if($dados['codigo_pagseguro']!="" && $dados['codigo_pagseguro']!=null){
			header('Location: '.$this->url_redirect.$dados['codigo_pagseguro']);
		}
		
		$dados = $this->generateUrl($dados,$retorno);
		
		$curl = curl_init($this->url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8'));
		curl_setopt($curl, CURLOPT_POSTFIELDS, $dados);
		$xml= curl_exec($curl);
		
		if($xml == 'Unauthorized'){
			//Insira seu código de prevenção a erros
			echo "Erro: Dados invalidos - Unauthorized";
			exit;//Mantenha essa linha
		}
		
		curl_close($curl);
		$xml_obj = simplexml_load_string($xml);
		if(count($xml_obj -> error) > 0){
			//Insira seu código de tratamento de erro, talvez seja útil enviar os códigos de erros.
			echo $xml."<br><br>";
			echo "Erro-> ".var_export($xml_obj->errors,true);
			exit;
		}
		header('Location: '.$this->url_redirect.$xml_obj->code);
	}
	
	//RECEBE UMA NOTIFICAÇÃO DO PAGSEGURO
	//RETORNA UM OBJETO CONTENDO OS DADOS DO PAGAMENTO
	public function executeNotification($POST){
		$url = $this->url_notificacao.$POST['notificationCode'].$this->email_token;
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		$transaction= curl_exec($curl);
		if($transaction == 'Unauthorized'){
			//TRANSAÇÃO NÃO AUTORIZADA
			
		    exit;
		}
		curl_close($curl);
		$transaction_obj = simplexml_load_string($transaction);
		return $transaction_obj;		
	}
	
	//Obtém o status de um pagamento com base no código do PagSeguro
	//Se o pagamento existir, retorna um código de 1 a 7
	//Se o pagamento não exitir, retorna NULL
	public function getStatusByCode($code){
		$url = $this->url_transactions.$code.$this->email_token;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
		$transaction = curl_exec($curl);
		if($transaction == 'Unauthorized') {
			//Insira seu código avisando que o sistema está com problemas
			//sugiro enviar um e-mail avisando para alguém fazer a manutenção
			exit;//Mantenha essa linha para evitar que o código prossiga
		}
		$transaction_obj = simplexml_load_string($transaction);
		
		if(count($transaction_obj -> error) > 0) {
		   //Insira seu código avisando que o sistema está com problemas
		   var_dump($transaction_obj);
		}		

		if(isset($transaction_obj->status))
			return $transaction_obj->status;
		else
			return NULL;
	}
	
	//Obtém o status de um pagamento com base na referência
	//Se o pagamento existir, retorna um código de 1 a 7
	//Se o pagamento não exitir, retorna NULL
	public function getStatusByReference($reference){
		$url = $this->url_transactions.$this->email_token."&reference=".$reference;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
		$transaction = curl_exec($curl);
		if($transaction == 'Unauthorized') {
			//Insira seu código avisando que o sistema está com problemas
			exit;//Mantenha essa linha para evitar que o código prossiga
		}
		$transaction_obj = simplexml_load_string($transaction);
		if(count($transaction_obj -> error) > 0) {
		   //Insira seu código avisando que o sistema está com problemas
		   var_dump($transaction_obj);
		}
		//print_r($transaction_obj);
		if(isset($transaction_obj->transactions->transaction->status))
			return $transaction_obj->transactions->transaction->status;
		else
			return NULL;
	}
	
	public function getStatusText($code){
		if($code>=1 && $code<=7)
			return $this->statusCode[$code];
		else
			return $this->statusCode[0];
	}
	
}
?>