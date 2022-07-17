<?php
require_once('util.php');

$filename = "win_12P_1707.csv";

$conn = new PDO("mysql:dbname=bolsav;host=localhost", "root", "admin");

//$nmPapel = 'WDOLFUT';
$nmPapel = 'WINFUT';
$dsVariacao = '12P';

$data;
$hora;
$abertura;
$maxima;
$minima;
$fechamento;
$saldo;
$i = 0;

//Verificar se arquivo existe
if (file_exists($filename)) {
    $file = fopen($filename, "r"); // r = modo leitura

    while ($row = fgets($file)) {
        $rowdata = explode('"', $row);

        if (isset($rowdata[1]) and $i > 0 ) {

          

            $data = retornaData($rowdata[1]);
            $hora = $rowdata[3];
            $abertura = retornaValorInteiro($rowdata[5]);
            $maxima = retornaValorInteiro($rowdata[7]);
            $minima = retornaValorInteiro($rowdata[9]);
            $fechamento = retornaValorInteiro($rowdata[11]);
            $saldo = retornaValorInteiro($rowdata[19]);

           // if(strtotime($data) >= strtotime('2022-07-05')){

            echo '<br>' . $data;
            echo '<br>' . strtotime($data);
            echo '<br> Data => ' . date("d/m/Y", strtotime($data));
            echo '<br> Hora => ' . $hora;
            echo '<br>' . $abertura;
            echo '<br>' . $maxima;
            echo '<br>' . $minima;
            echo '<br> fechamento => ' . $fechamento;
            echo '<br>' . $saldo;
            echo '<hr>';
           // }

           $stmt = $conn->prepare("insert into tbl_agressao(nm_papel,ds_variacao,dt_operacao,hr_operacao,vl_abertura,vl_maxima,vl_minima,vl_fechamento,vl_saldo)".
                                  "values(:nm_papel,:ds_variacao,:dt_operacao,:hr_operacao,:vl_abertura,:vl_maxima,:vl_minima,:vl_fechamento,:vl_saldo)");

           $stmt->bindParam(":nm_papel",$nmPapel);
           $stmt->bindParam(":ds_variacao",$dsVariacao);
           $stmt->bindParam(":dt_operacao",$data);
           $stmt->bindParam(":hr_operacao",$hora);
           $stmt->bindParam(":vl_abertura",$abertura);
           $stmt->bindParam(":vl_maxima",$maxima);
           $stmt->bindParam(":vl_minima",$minima);
           $stmt->bindParam(":vl_fechamento",$fechamento);
           $stmt->bindParam(":vl_saldo",$saldo);

           $stmt->execute();
            

        }
        $i++;

    }

    fclose($file);
}
