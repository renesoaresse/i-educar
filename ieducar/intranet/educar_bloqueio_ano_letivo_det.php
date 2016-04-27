<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	*																	     *
	*	@author Prefeitura Municipal de Itaja�								 *
	*	@updated 29/03/2007													 *
	*   Pacote: i-PLB Software P�blico Livre e Brasileiro					 *
	*																		 *
	*	Copyright (C) 2006	PMI - Prefeitura Municipal de Itaja�			 *
	*						ctima@itajai.sc.gov.br					    	 *
	*																		 *
	*	Este  programa  �  software livre, voc� pode redistribu�-lo e/ou	 *
	*	modific�-lo sob os termos da Licen�a P�blica Geral GNU, conforme	 *
	*	publicada pela Free  Software  Foundation,  tanto  a vers�o 2 da	 *
	*	Licen�a   como  (a  seu  crit�rio)  qualquer  vers�o  mais  nova.	 *
	*																		 *
	*	Este programa  � distribu�do na expectativa de ser �til, mas SEM	 *
	*	QUALQUER GARANTIA. Sem mesmo a garantia impl�cita de COMERCIALI-	 *
	*	ZA��O  ou  de ADEQUA��O A QUALQUER PROP�SITO EM PARTICULAR. Con-	 *
	*	sulte  a  Licen�a  P�blica  Geral  GNU para obter mais detalhes.	 *
	*																		 *
	*	Voc�  deve  ter  recebido uma c�pia da Licen�a P�blica Geral GNU	 *
	*	junto  com  este  programa. Se n�o, escreva para a Free Software	 *
	*	Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA	 *
	*	02111-1307, USA.													 *
	*																		 *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require_once ("include/clsBase.inc.php");
require_once ("include/clsDetalhe.inc.php");
require_once ("include/clsBanco.inc.php");
require_once( "include/pmieducar/geral.inc.php" );

class clsIndexBase extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} i-Educar - Bloqueio do ano letivo" );
		$this->processoAp = "21251";
		$this->addEstilo("localizacaoSistema");
	}
}

class indice extends clsDetalhe
{
	/**
	 * Titulo no topo da pagina
	 *
	 * @var int
	 */
	var $titulo;

	var $ref_cod_instituicao;
	var $ref_ano;
	var $data_inicio;
	var $data_fim;

	function Gerar()
	{
		@session_start();
		$this->pessoa_logada = $_SESSION['id_pessoa'];
		session_write_close();

		$this->titulo = "Bloqueio do ano letivo - Detalhe";


		$this->ref_cod_instituicao=$_GET["ref_cod_instituicao"];
		$this->ref_ano=$_GET["ref_ano"];

		$tmp_obj = new clsPmieducarBloqueioAnoLetivo( $this->ref_cod_instituicao, $this->ref_ano );
		$registro = $tmp_obj->detalhe();

		if( ! $registro )
		{
			header( "location: educar_bloqueio_ano_letivo_lst.php" );
			die();
		}

		if( $registro["instituicao"] )
		{
			$this->addDetalhe( array( "Institui&ccedil;&atilde;o", "{$registro["instituicao"]}") );
		}
		if( $registro["ref_ano"] )
		{
			$this->addDetalhe( array( "Ano", "{$registro["ref_ano"]}") );
		}
		if( $registro["data_inicio"] )
		{
			$this->addDetalhe( array( "Data inicial permitida", dataToBrasil($registro['data_inicio'])) );
		}
		if( $registro["data_fim"] )
		{
			$this->addDetalhe( array( "Data final permitida", dataToBrasil($registro['data_fim'])) );
		}

		//** Verificacao de permissao para cadastro
		$obj_permissao = new clsPermissoes();

		if($obj_permissao->permissao_cadastra(21251, $this->pessoa_logada,3))
		{
			$this->url_novo = "educar_bloqueio_ano_letivo_cad.php";
			$this->url_editar = "educar_bloqueio_ano_letivo_cad.php?ref_cod_instituicao={$registro["ref_cod_instituicao"]}&ref_ano={$registro["ref_ano"]}";
		}
		//**
		$this->url_cancelar = "educar_bloqueio_ano_letivo_lst.php";
		$this->largura = "100%";

    $localizacao = new LocalizacaoSistema();
    $localizacao->entradaCaminhos( array(
         $_SERVER['SERVER_NAME']."/intranet" => "In&iacute;cio",
         "educar_index.php"                  => "i-Educar - Escola",
         ""                                  => "Detalhe do bloqueio do ano letivo"
    ));
    $this->enviaLocalizacao($localizacao->montar());
	}
}

// cria uma extensao da classe base
$pagina = new clsIndexBase();
// cria o conteudo
$miolo = new indice();
// adiciona o conteudo na clsBase
$pagina->addForm( $miolo );
// gera o html
$pagina->MakeAll();
?>