<?php
require_once('sql.php');

function retornaValorInteiro($vlPreco)
{
    $valor = $vlPreco;
    $pos = strpos($valor, ',');

    if ($pos > 0) {
        $valor = substr($valor, 0, $pos);
        $valor = str_replace('.', '', $valor);
    }

    return str_replace('.', '', $valor);
}


function retornaValorDecimal($vlPreco)
{
    $valor = str_replace(',', '.', $vlPreco);

    return $valor;
}

function retornaData($data)
{
    $dia = substr($data, 0, 2);
    $mes = substr($data, 3, 2);
    $ano = substr($data, 6, 4);
    $data = $ano . '-' . $mes . '-' . $dia;

    return $data;
}


function retornaDataBr($data)
{
    $dia = substr($data, 8, 2);
    $mes = substr($data, 5, 2);
    $ano = substr($data, 0, 4);

    $data = $dia . '/' . $mes . '/' . $ano;

    return $data;
}

function retornaCustos($nmPapel, $qtOperacoes, $qtContratos)
{
    $custoOperacao = 0;
    $custoContrato = $nmPapel == 'WINFUT' ? 0.22 : 1;

    $custoOperacao = $qtContratos * $qtOperacoes * $custoContrato * 2;

    return $custoOperacao;
}

function retornaPapelVariacao()
{
    $sql = new Sql();
    $conn = $sql->retornaPdo();

    $stmt = $conn->prepare("select distinct concat( concat( ta.nm_papel , ' - ') , ta.ds_variacao)papel
                             from tbl_agressao ta
                             order by concat( concat( ta.nm_papel , ' - ') , ta.ds_variacao)
                            ");

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
}

function retornaPapel($papelParam){
    $papel = trim(substr($papelParam,0,6));
    return $papel;
}

function retornaVariacao($varParam){
    $variacao = trim(substr($varParam,8,6));;
    return $variacao;
}
