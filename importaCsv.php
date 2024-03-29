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

foreach ($arquivos as $key => $arquivo) {
    insereDados($arquivo);
}

function insereDados($filename)
{

    $conn = new PDO("mysql:dbname=bolsav;host=localhost", "root", "admin");

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
    $i = 0;

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

                $stmt = $conn->prepare("insert into tbl_agressao(nm_papel,ds_variacao,dt_operacao,hr_operacao,vl_abertura,vl_maxima,vl_minima,vl_fechamento,vl_saldo)" .
                    "values(:nm_papel,:ds_variacao,:dt_operacao,:hr_operacao,:vl_abertura,:vl_maxima,:vl_minima,:vl_fechamento,:vl_saldo)");

                $stmt->bindParam(":nm_papel", $nmPapel);
                $stmt->bindParam(":ds_variacao", $dsVariacao);
                $stmt->bindParam(":dt_operacao", $data);
                $stmt->bindParam(":hr_operacao", $hora);
                $stmt->bindParam(":vl_abertura", $abertura);
                $stmt->bindParam(":vl_maxima", $maxima);
                $stmt->bindParam(":vl_minima", $minima);
                $stmt->bindParam(":vl_fechamento", $fechamento);
                $stmt->bindParam(":vl_saldo", $saldo);

                $stmt->execute();
            }
            $i++;
        }

        fclose($file);
    }
}
