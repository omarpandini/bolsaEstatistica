<?php
require_once('util.php');

$filename = "trades_0407.csv";

$conn = new PDO("mysql:dbname=bolsav;host=localhost", "root", "admin");

$nmPapel;
$hrOperacao;
$idOperacao;
$vlPrecoCompra;
$vlPrecoVenda;
$dtOperacao;
$qtdContratos;
$vlResultado;

$nmOperador = 'ANDREIA';
$idReplay = 'N';
$i = 1;


//Verificar se arquivo existe
if (file_exists($filename)) {
    $file = fopen($filename, "r"); // r = modo leitura
    $headers = explode(",", fgets($file)); //fgets retorna true ou false caso ainda tenha linhas

    while ($row = fgets($file)) {

        if ($i > 1) {
            $rowdata = explode('"', $row);
            //var_dump($rowdata);

            if (isset($rowdata[1])) {
                $nmPapel = $rowdata[1];
                $hrOperacao = $rowdata[3];
                $idOperacao = $rowdata[7];
                $vlPrecoCompra = retornaValorInteiro($rowdata[9]);
                $vlPrecoVenda = retornaValorInteiro($rowdata[11]);
                $dtOperacao = retornaData($rowdata[13]);
                $qtdContratos = $rowdata[15];
                $vlResultado = retornaValorDecimal($rowdata[17]);

                $stmt = $conn->prepare("insert into tbl_operacoes_bolsa(nm_papel,hr_abertura,cd_operacao,vl_preco_compra,vl_preco_venda,dt_operacao,qtd_contratos,vl_resultado,nm_operador,id_replay)
                                       values(:nm_papel,:hr_abertura,:cd_operacao,:vl_preco_compra,:vl_preco_venda,:dt_operacao,:qtd_contratos,:vl_resultado,:nm_operador,:id_replay)");

                $stmt->bindParam(":nm_papel", $nmPapel);
                $stmt->bindParam(":hr_abertura", $hrOperacao);
                $stmt->bindParam(":cd_operacao", $idOperacao);
                $stmt->bindParam(":vl_preco_compra", $vlPrecoCompra);
                $stmt->bindParam(":vl_preco_venda", $vlPrecoVenda);
                $stmt->bindParam(":dt_operacao", $dtOperacao);
                $stmt->bindParam(":qtd_contratos", $qtdContratos);
                $stmt->bindParam(":vl_resultado", $vlResultado);
                $stmt->bindParam(":nm_operador", $nmOperador);
                $stmt->bindParam(":id_replay", $idReplay);

                $stmt->execute();

                echo $nmPapel . '<br>';
                echo $hrOperacao . '<br>';
                echo $idOperacao . '<br>';
                echo $vlPrecoCompra . '<br>';
                echo $vlPrecoVenda . '<br>';
                echo $dtOperacao . '<br>';
                echo $qtdContratos . '<br>';
                echo $vlResultado . '<br>';
            }
        }

        $i++;
    }

    fclose($file);
}

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
