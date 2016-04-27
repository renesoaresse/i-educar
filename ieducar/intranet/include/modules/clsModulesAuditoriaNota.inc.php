<?php
// error_reporting(E_ERROR);
// ini_set("display_errors", 1);
/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *                     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Gabriel Matos de Souza <gabriel@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Module
 * @since     07/2015
 * @version   $Id$
 */

require_once( "include/pmieducar/geral.inc.php" );

/**
 * clsModulesAuditoria class.
 *
 * @author    Gabriel Matos de Souza <gabriel@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Module
 * @since     07/2015
 * @version   @@package_version@@
 */

class clsModulesAuditoriaNota {
	var $notaAntiga;
	var $notaNova;
	var $stringNotaAntiga;
	var $stringNotaNova;
	var $usuario;
	var $operacao;
	var $rotina;
	var $dataHora;
	var $turma;

	const OPERACAO_INCLUSAO = 1;
	const OPERACAO_ALTERACAO = 2;
	const OPERACAO_EXCLUSAO = 3;

	function clsModulesAuditoriaNota($notaAntiga, $notaNova, $turmaId){

		//Foi necessário enviar turma pois não é possível saber a turma atual somente através da matrícula
		$this->turma = $turmaId;

		$this->usuario = $this->getUsuarioAtual();
		$this->rotina = "notas";

		$this->notaAntiga = $notaAntiga;
		$this->notaNova = $notaNova;

		if(!is_null($this->notaAntiga)){
			$this->stringNotaAntiga = $this->montaStringInformacoes($this->montaArrayInformacoes($this->notaAntiga));
		}

		if(!is_null($this->notaNova)){
			$this->stringNotaNova = $this->montaStringInformacoes($this->montaArrayInformacoes($this->notaNova));
		}

		$this->dataHora = date('Y-m-d H:i:s');

	}

	public function cadastra(){

		$db = new clsBanco();
		$this->_schema = "modules.";
		$this->_tabela = "{$this->_schema}auditoria";
		$separador = "";
		$valores = "";

		if(!is_null($this->stringNotaAntiga) && !is_null($this->stringNotaNova)){
			$this->operacao = self::OPERACAO_ALTERACAO;
		}elseif(!is_null($this->stringNotaAntiga) && is_null($this->stringNotaNova)){
			$this->operacao = self::OPERACAO_EXCLUSAO;
		}elseif(is_null($this->stringNotaAntiga) && !is_null($this->stringNotaNova)){
			$this->operacao = self::OPERACAO_INCLUSAO;
		}

		if(is_string($this->usuario)){
			$campos .= "{$separador}usuario";
			$valores .= "{$separador}'{$this->usuario}'";
			$separador = ", ";
		}

		$campos .= "{$separador}operacao";
		$valores .= "{$separador}'{$this->operacao}'";
		$separador = ", ";

		$campos .= "{$separador}rotina";
		$valores .= "{$separador}'{$this->rotina}'";
		$separador = ", ";

		if(is_string($this->stringNotaAntiga)){
			$campos .= "{$separador}valor_antigo";
			$valores .= "{$separador}'{$this->stringNotaAntiga}'";
			$separador = ", ";
		}

		if(is_string($this->stringNotaNova)){
			$campos .= "{$separador}valor_novo";
			$valores .= "{$separador}'{$this->stringNotaNova}'";
			$separador = ", ";
		}

		$campos .= "{$separador}data_hora";
		$valores .= "{$separador}'{$this->dataHora}'";
		$separador = ", ";

		$db->Consulta( "INSERT INTO {$this->_tabela} ( $campos ) VALUES( $valores )" );

	}


	private function montaStringInformacoes($arrayInformacoes){
		if(empty($arrayInformacoes)){return null;}

		$stringDados = "";
		$separadorDados = ",";
		$separadorInformacoes = ":";
		$inicioString = "{";
		$fimString = "}";

		$stringDados .= $inicioString;

		foreach($arrayInformacoes as $campo => $valor){
			$stringDados .= $campo;
			$stringDados .= $separadorInformacoes;
			$stringDados .= $valor;
			$stringDados .= $separadorDados;
		}

		//remove o último valor, qual seria uma vírgula
		$stringDados = substr($stringDados, 0, -1);

		$stringDados .= $fimString;

		return $stringDados;
	}

	private function montaArrayInformacoes($nota){

		if(!($nota instanceof Avaliacao_Model_NotaComponente)){return null;}
			$componenteCurricularId = $nota->get('componenteCurricular');
			$componenteCurricular = $this->getNomeComponenteCurricular($componenteCurricularId);

			$notaAlunoId = $nota->get('notaAluno');

			$arrayInformacoes = $this->getInfosMatricula($notaAlunoId);

			$arrayInformacoes += array("nota" => $nota->notaArredondada,
																 "etapa" => $nota->etapa,
																 "componenteCurricular" => $componenteCurricular);

			return $arrayInformacoes;

	}

	private function getNomeComponenteCurricular($componenteCurricularId){
		$mapper = new ComponenteCurricular_Model_ComponenteDataMapper();
		$componenteCurricular = $mapper->find($componenteCurricularId)->nome;

		return $componenteCurricular;
	}

	private function getInfosMatricula($notaAlunoId){
		$mapper = new Avaliacao_Model_NotaAlunoDataMapper();
		$matriculaId = $mapper->find($notaAlunoId)->matricula;

		$objMatricula = new clsPmieducarMatricula($matriculaId);
		$detMatricula = $objMatricula->detalhe();

		$instituicaoId = $detMatricula["ref_cod_instituicao"];
		$escolaId = $detMatricula["ref_ref_cod_escola"];
		$cursoId = $detMatricula["ref_cod_curso"];
		$serieId = $detMatricula["ref_ref_cod_serie"];
		$alunoId = $detMatricula["ref_cod_aluno"];
		$turmaId = $this->turma;

		$nomeInstitucao = $this->getNomeInstituicao($instituicaoId);
		$nomeEscola = $this->getNomeEscola($escolaId);
		$nomeCurso = $this->getNomeCurso($cursoId);
		$nomeSerie = $this->getNomeSerie($serieId);
		$nomeAluno = $this->getNomeAluno($alunoId);
		$nomeTurma = $this->getNomeTurma($turmaId);

		return array("instituicao" => $nomeInstitucao,
								 "instituicao_id" => $instituicaoId,
								 "escola" => $nomeEscola,
								 "escola_id" => $escolaId,
								 "curso" => $nomeCurso,
								 "curso_id" => $cursoId,
								 "serie" => $nomeSerie,
								 "serie_id" => $serieId,
								 "turma" => $nomeTurma,
								 "turma_id" => $turmaId,
								 "aluno" => $nomeAluno,
								 "aluno_id" => $alunoId);

	}
	private function getNomeInstituicao($instituicaoId){
		$objInstituicao = new clsPmieducarInstituicao($instituicaoId);
		$detInstituicao = $objInstituicao->detalhe();
		$nomeInstitucao = $detInstituicao["nm_instituicao"];

		return $nomeInstitucao;
	}
	private function getNomeEscola($escolaId){
		$objEscola = new clsPmieducarEscola($escolaId);
		$detEscola = $objEscola->detalhe();
		$nomeEscola = $detEscola["nome"];

		return $nomeEscola;
	}
	private function getNomeCurso($cursoId){
		$objCurso = new clsPmieducarCurso($cursoId);
		$detCurso = $objCurso->detalhe();
		$nomeCurso = $detCurso["nm_curso"];

		return $nomeCurso;
}
	private function getNomeSerie($serieId){
		$objSerie = new clsPmieducarSerie($serieId);
		$detSerie = $objSerie->detalhe();
		$nomeSerie = $detSerie["nm_serie"];

		return $nomeSerie;
	}

	private function getNomeAluno($alunoId){
		$objAluno = new clsPmieducarAluno($alunoId);
		$detAluno = $objAluno->detalhe();
		$pessoaId = $detAluno["ref_idpes"];

		$objPessoa = new clsPessoa_($pessoaId);
		$detPessoa = $objPessoa->detalhe();
		$nomePessoa = $detPessoa["nome"];

		$nomePessoa = Portabilis_String_Utils::toLatin1($nomePessoa);

		return $nomePessoa;

	}
	private function getNomeTurma($turmaId){
		$objTurma = new clsPmieducarTurma($turmaId);
		$detTurma = $objTurma->detalhe();
		$nomeTurma = $detTurma["nm_turma"];

		return $nomeTurma;
	}
	private function getUsuarioAtual(){
		@session_start();
   	$pessoaId = $_SESSION['id_pessoa'];
   	@session_write_close();
   	$objFuncionario = new clsFuncionario($pessoaId);
   	$detFuncionario = $objFuncionario->detalhe();
   	$matricula = $detFuncionario["matricula"];

   	return $pessoaId . " - " . $matricula;
	}

}
?>