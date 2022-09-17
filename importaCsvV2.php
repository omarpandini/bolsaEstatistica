<?php
require_once('util.php');

$arquivos = array('WDOFUT_4P.csv'
                 ,'WDOFUT_5P.csv'
                 ,'WDOFUT_6P.csv'
                 ,'WDOFUT_8P.csv'
                 ,'WINFUT_10P.csv'
                 ,'WINFUT_12P.csv'
                 ,'WINFUT_15P.csv'
                 ,'WINFUT_20P.csv'
                 ,'WINFUT_14P.csv'
                 ,'WINFUT_16P.csv'
                 ,'WINFUT_18P.csv'
                 ,'WINFUT_22P.csv'
                 ,'WINFUT_24P.csv'
                 ,'WINFUT_8R.CSV'
                 ,'WINFUT_10R.CSV'
                 ,'WINFUT_28R.CSV'
                 ,'WINFUT_14R.CSV'
                 ,'WINFUT_16R.CSV'
                 ,'WINFUT_18R.CSV'
                 ,'WINFUT_20R.CSV'
                 ,'WINFUT_22R.CSV'
                 ,'WINFUT_24R.CSV'
                 ,'WINFUT_26R.CSV'
                );



$conn = new PDO("mysql:dbname=bolsav;host=localhost", "root", "");
$conn->beginTransaction();/* Inicia a transação */

foreach ($arquivos as $key => $arquivo) {
    insereDados($arquivo,$conn);
}

$conn->commit();

function insereDados($filename,$conn)
{


    $nmPapel = substr($filename, 0, 6);
    $dsVariacao = substr($filename, 7, 3);
    $dsVariacao = str_replace('.', '', $dsVariacao);

    $data;
    $hora;
    $abertura;
    $maxima;
    $minima;
    $fechamento;
    $saldo;
    $volume;
    $ema9;
    $i = 0;
    $registros = 0;
    $linhasInseridas = 0;

    //Verificar se arquivo existe
    if (file_exists($filename)) {
        $file = fopen($filename, "r"); // r = modo leitura

        while ($row = fgets($file)) {
            $rowdata = explode('"', $row);


            if (isset($rowdata[1]) and $i > 0) {


                $data = retornaData($rowdata[1]);
                $hora = $rowdata[3];
                $abertura = retornaValorInteiro($rowdata[5]);
                $maxima = retornaValorInteiro($rowdata[7]);
                $minima = retornaValorInteiro($rowdata[9]);
                $fechamento = retornaValorInteiro($rowdata[11]);
                $saldo = retornaValorInteiro($rowdata[21]);
                $ema9 = retornaValorInteiro($rowdata[15]);

                if (empty($ema9)) {
                    $ema9 = 0;
                };
             

                // if(strtotime($data) >= strtotime('2022-07-05')){

                    /*
                echo '<br> Data => ' . $data;
                echo '<br>' . strtotime($data);
                echo '<br> Data => ' . date("d/m/Y", strtotime($data));
                echo '<br> Hora => ' . $hora;
                echo '<br>abertura ' . $abertura;
                echo '<br>maxima ' . $maxima;
                echo '<br>miniima ' . $minima;
                echo '<br> fechamento => ' . $fechamento;
                echo '<br> ema9 ' . $ema9;
                echo '<br> saldo ' . $saldo;
                echo '<hr>';
                */

                // }

                
                $stmt = $conn->prepare("insert into tbl_agressao(nm_papel,ds_variacao,dt_operacao,hr_operacao,vl_abertura,vl_maxima,vl_minima,vl_fechamento,vl_saldo,vl_ema_9)" .
                    "values(:nm_papel,:ds_variacao,:dt_operacao,:hr_operacao,:vl_abertura,:vl_maxima,:vl_minima,:vl_fechamento,:vl_saldo,:vl_ema_9)");

                $stmt->bindParam(":nm_papel", $nmPapel);
                $stmt->bindParam(":ds_variacao", $dsVariacao);
                $stmt->bindParam(":dt_operacao", $data);
                $stmt->bindParam(":hr_operacao", $hora);
                $stmt->bindParam(":vl_abertura", $abertura);
                $stmt->bindParam(":vl_maxima", $maxima);
                $stmt->bindParam(":vl_minima", $minima);
                $stmt->bindParam(":vl_fechamento", $fechamento);
                $stmt->bindParam(":vl_saldo", $saldo);
                $stmt->bindParam(":vl_ema_9", $ema9);

                $registros++;

                if (!$stmt->execute()) {

                    $erro = $stmt->errorInfo();

                    if ($erro[1] != 1062 ) { // Erro de chave primária
                        echo '<p style="background-color:red;color:white;font-size:30px;padding:10px;width:70%;margin:0 auto 0 auto;text-align:center;"><strong>Erro '.$erro[1].'</strong> - '.$erro[2].'. Registro '.$registros.'<p>';
                        echo '<br>';
                        $conn->rollBack();
                        die();
                    }
                }else{
                    $linhasInseridas += $stmt->rowCount();
                }
                
            }
            $i++;
        }

        fclose($file);
        echo '<p style="background-color:#0d8c2e;color:white;font-size:30px;padding:10px;width:70%;margin:0 auto 0 auto;text-align:center;">Arquivo '.$filename .' Linhas Inseridas '.$linhasInseridas.' de '.$registros.'</p><br>';
    }
}
