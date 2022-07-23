<?php
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
    $data = $ano.'-'.$mes.'-'.$dia;

    return $data;
}


function retornaDataBr($data)
{
    $dia = substr($data,8,2);
    $mes = substr($data,5,2);
    $ano = substr($data,0,4);

    $data = $dia.'/'.$mes.'/'.$ano;

    return $data;
}

?>