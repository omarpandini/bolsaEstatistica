<?php

if (isset($_POST['submit'])) {

    $dtOperacaoIni = $_POST['dtInicial'];
    $dtOperacaoFim = $_POST['dtFinal'];
    $nmPapel = $_POST['nmPapel'];
    $dsVariacao = $_POST['idVariacao'];
    $hrOperacao = $_POST['hrOperacao'];
    $idDebug = $_POST['idDebug'];

    $nrPontos = substr($dsVariacao,0,strpos($dsVariacao,'P')) ;

    echo 'pos '.$nrPontos ;

}





//echo 'papel '. $_POST['nmPapel'];


$conn = new PDO("mysql:dbname=bolsav;host=localhost", "root", "admin");

$stmt = $conn->prepare("
select *
      ,abs(tbl.vl_saldo)vl_saldo_abs
  from tbl_agressao tbl
where tbl.dt_operacao between :dt_operacao_ini and :dt_operacao_fim 
  and tbl.nm_papel = :nm_papel
  and tbl.ds_variacao = :ds_variacao
  and to_number(substr(tbl.hr_operacao,1,2)) < :hr_operacao
order by tbl.id_reg desc
");

$stmt->bindParam(":dt_operacao_ini", $dtOperacaoIni);
$stmt->bindParam(":dt_operacao_fim", $dtOperacaoFim);
$stmt->bindParam(":hr_operacao", $hrOperacao );
$stmt->bindParam(":nm_papel", $nmPapel);
$stmt->bindParam(":ds_variacao", $dsVariacao);

$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);


//Array que irá armazenar os filtros de mínima e máxima e o título do card
$filtro = array();

array_push($filtro, array('titulo' => 'Barras Saldo 0 - 999', 'vl_minimo' => 0, 'vl_maximo' => 999));
   
for ($i = 1; $i <= 15; $i++) {
    $vl_minimo = ($i * 1000);
    $vl_maximo = ($i * 1000) + 999;
    $titulo = 'Barras Saldo ' . $vl_minimo . ' - ' . $vl_maximo;
    array_push($filtro, array('titulo' => $titulo, 'vl_minimo' => $vl_minimo, 'vl_maximo' => $vl_maximo));
}


//var_dump($filtro);
//die();

//Array que irá armazenar os resultados
$resultado = array();


foreach ($filtro as $value) {
    $contadorBarras = 0;
    $contadorBarrasSaldoPos = 0;
    $contadorBarrasSaldoNeg = 0;
    $contadorBarrasPosSaldoPos = 0;
    $contadorBarrasPosSaldoNeg = 0;

    $contadorBarrasNegSaldoPos = 0;
    $contadorBarrasNegSaldoNeg = 0;

    $contadorOperacoesCp = 0;
    $contadorOperacoesCpGain = 0;
    $contadorOperacoesCpLoss = 0;

    $contadorOperacoesVd = 0;
    $contadorOperacoesVdGain = 0;
    $contadorOperacoesVdLoss = 0;

    $debug = '';
    $gatilho = '';

    foreach ($results as $sql) {

        $debug = 'hora ' . $sql['hr_operacao'] . ' ' . $value['vl_minimo'] . ' - ' . $value['vl_maximo'];;

        //Gatilho de operação foi ativado CP = Compra  VD = Venda
        switch ($gatilho) {
            case 'CP':  // Compra

                if ($sql['vl_fechamento'] > $sql['vl_abertura']) {
                    $debug .= ' GAIN ';
                    $contadorOperacoesCpGain++;
                } else {
                    $debug .= ' LOSS ';
                    $contadorOperacoesCpLoss++;
                }

                $gatilho = '';

                break;
            case 'VD':  // Venda

                if ($sql['vl_fechamento'] < $sql['vl_abertura']) {
                    $debug .= ' GAIN ';
                    $contadorOperacoesVdGain++;
                } else {
                    $debug .= ' LOSS ';
                    $contadorOperacoesVdLoss++;
                }

                $gatilho = '';
                break;

            default:
                # code...
                break;
        }

        if ($sql['vl_saldo_abs'] >= $value['vl_minimo'] and $sql['vl_saldo_abs'] <=  $value['vl_maximo']) {
            $contadorBarras++;
            $debug = 'hora ' . $sql['hr_operacao'] . ' ' . $value['vl_minimo'] . ' - ' . $value['vl_maximo'];

            if ($sql['vl_saldo'] > 0) {
                $contadorBarrasSaldoPos++;

                $debug .= ' saldo pos <strong>' . $sql['vl_saldo'] . '</strong>';

                if ($sql['vl_fechamento'] > $sql['vl_abertura']) {
                    $contadorBarrasPosSaldoPos++;
                    $debug .= ' barra pos';

                    if (empty($gatilho)) {
                        $gatilho = 'CP';
                        $debug .= '<span style="background-color:#31d65d;"><strong> GATILHO ' . $gatilho . '</strong></span>';
                        $contadorOperacoesCp++;
                    }
                } else {
                    $contadorBarrasPosSaldoNeg++;
                    $debug .= ' barra neg';
                }
            } else {
                $contadorBarrasSaldoNeg++;

                $debug .= ' saldo neg <strong>' . $sql['vl_saldo'] . '</strong>';;

                if ($sql['vl_fechamento'] > $sql['vl_abertura']) {
                    $contadorBarrasNegSaldoPos++;
                    $debug .= ' barra pos';
                } else {
                    $contadorBarrasNegSaldoNeg++;
                    $debug .= ' barra neg';


                    if (empty($gatilho)) {
                        $gatilho = 'VD';
                        $contadorOperacoesVd++;
                        $debug .= '<span style="background-color:#db3a1a;color:white;"><strong> GATILHO ' . $gatilho . '</strong></span>';
                    }
                }
            }
        }
        
        if ($idDebug == 'S') {
            echo $debug . '<br>';
        }
    }

    array_push(
        $resultado,
        array(
            'titulo' => $value['titulo'],
            'vl_minimo' => $value['vl_minimo'],
            'vl_maximo' => $value['vl_maximo'],
            'total_barras' => $contadorBarras,
            'total_barras_saldo_pos' => $contadorBarrasSaldoPos,
            'total_barras_saldo_neg' => $contadorBarrasSaldoNeg,
            'total_barras_pos_saldo_pos' => $contadorBarrasPosSaldoPos,
            'total_barras_pos_saldo_neg' => $contadorBarrasPosSaldoNeg,
            'total_barras_neg_saldo_pos' => $contadorBarrasNegSaldoPos,
            'total_barras_neg_saldo_neg' => $contadorBarrasNegSaldoNeg,
            'total_operacoes_cp' => $contadorOperacoesCp,
            'total_operacoes_cp_gain' => $contadorOperacoesCpGain,
            'total_operacoes_cp_loss' => $contadorOperacoesCpLoss,
            'total_operacoes_vd' => $contadorOperacoesVd,
            'total_operacoes_vd_gain' => $contadorOperacoesVdGain,
            'total_operacoes_vd_loss' => $contadorOperacoesVdLoss
        )

    );
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Estatísticas Agressão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <style>
        #header {
            display: flex;
            justify-content: center;
        }

        h1 {
            text-align: center;
            border-radius: 10px;
            padding: 10px;
            width: 50%;
        }
    </style>
</head>

<body>

    <div id="header">
        <h1>Estatísticas</h1>
    </div>

    <div class="container">

        <div class="p-5 mb-4 bg-light rounded-3" zn_id="3">
            <div class="container-fluid py-5" zn_id="9">
                <h1 class="display-5 fw-bold" zn_id="10">Parâmetros</h1>

                <form action="#" method="post">
                    <div class="mb-3 row">
                        <label for="dtInicial" class="col-sm-2 col-form-label">Data Inicial</label>
                        <div class="col-sm-2">
                            <input type="date" class="form-control" id="dtInicial" name="dtInicial" value="<?php echo $dtOperacaoIni;?>">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="dtFinal" class="col-sm-2 col-form-label">Data Final</label>
                        <div class="col-sm-2">
                            <input type="date" class="form-control" id="dtFinal" name="dtFinal" value="<?php echo $dtOperacaoFim; ?>">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="nmPapel" class="col-sm-2 col-form-label">Papel</label>
                        <div class="col-sm-2">
                            <select class="form-select" aria-label="Default select example" id="nmPapel" name="nmPapel">
                                <option <?php if($nmPapel == 'WINFUT') { ?> selected <?php }; ?>  value="WINFUT">Mini Índice</option>
                                <option <?php if($nmPapel == 'WDOFUT') { ?> selected <?php }; ?>  value="WDOFUT">Mini Dólar</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="idVariacao" class="col-sm-2 col-form-label">Variação</label>
                        <div class="col-sm-2">
                            <select class="form-select" aria-label="Default select example" id="idVariacao" name="idVariacao">
                                <option <?php if($dsVariacao == '4P') { ?> selected <?php }; ?>  value="4P">4P</option>
                                <option <?php if($dsVariacao == '5P') { ?> selected <?php }; ?> value="5P">5P</option>
                                <option <?php if($dsVariacao == '6P') { ?> selected <?php }; ?> value="6P">6P</option>
                                <option <?php if($dsVariacao == '10P') { ?> selected <?php }; ?> value="10P">10P</option>
                                <option <?php if($dsVariacao == '12P') { ?> selected <?php }; ?> value="12P">12P</option>
                                <option <?php if($dsVariacao == '15P') { ?> selected <?php }; ?> value="15P">15P</option>
                                <option <?php if($dsVariacao == '20P') { ?> selected <?php }; ?> value="20P">20P</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="idVariacao" class="col-sm-2 col-form-label">Max Horário</label>
                        <div class="col-sm-2">
                          <input type="number" min="10" max="17" class="form-control" id="hrOperacao" name="hrOperacao" value="<?php echo empty($hrOperacao)? 10 : $hrOperacao; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3 row">
                        <label for="idVariacao" class="col-sm-2 col-form-label">Debug?</label>
                        <div class="col-sm-2">
                            <select class="form-select" aria-label="Default select example" id="idDebug" name="idDebug">
                                <option selected value="N">Não</option>
                                <option  value="S">Sim</option>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-lg" type="submit" name="submit" zn_id="12">Buscar</button>

                </form>
            </div>
        </div>

        <br><br>

        <?php
        retornaCard($resultado);
        ?>


    </div>

</body>

</html>

<?php

function retornaCard($resultado)
{

    $i = 1;
    $card = '';

    foreach ($resultado as $value) {
        if ($i == 1) {
            $card .= '<div class="row">';
        }

        $total_operacoes_cp = $value['total_operacoes_cp'];
        if ($total_operacoes_cp == 0) {
            $total_operacoes_cp = 1;
        }


        $total_operacoes_vd = $value['total_operacoes_vd'];
        if ($total_operacoes_vd == 0) {
            $total_operacoes_vd = 1;
        }

        $percAcertoCp = $value['total_operacoes_cp_gain'] / $total_operacoes_cp * 100;
        $percAcertoCp = round($percAcertoCp, 2);

        $percAcertoVd = $value['total_operacoes_vd_gain'] / $total_operacoes_vd * 100;
        $percAcertoVd = round($percAcertoVd, 2);

        $resultado = ( $value['total_operacoes_cp_gain'] -  $value['total_operacoes_cp_loss']) + ($value['total_operacoes_vd_gain']  - $value['total_operacoes_vd_loss']);
        $resultadoInverso = ( $value['total_operacoes_cp_loss'] -  $value['total_operacoes_cp_gain']) + ($value['total_operacoes_vd_loss']  - $value['total_operacoes_vd_gain']);


        $card .= '<div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h5>' . $value['titulo'] . '</h5>
                        </div>
                        <div class="card-body">

                            <h4>Total Barras <span class="badge bg-secondary">' . $value['total_barras'] . '</span></h4>
                            <h4 style="color:green;">Total Saldo Pos <span class="badge bg-secondary">' . $value['total_barras_saldo_pos'] . '</span> P -' . $value['total_barras_pos_saldo_pos'] . ' N - ' . $value['total_barras_pos_saldo_neg'] . '</h4>
                            <h4 style="color:red;">Total Saldo Neg <span class="badge bg-secondary">' . $value['total_barras_saldo_neg'] . '</span>  P -' . $value['total_barras_neg_saldo_pos'] . ' N - ' . $value['total_barras_neg_saldo_neg'] . '</h4>
                            <h4>Total Operações CP <span class="badge bg-secondary">' . $value['total_operacoes_cp'] . '</span> G - ' . $value['total_operacoes_cp_gain'] . ' | L -' . $value['total_operacoes_cp_loss'] . ' | Acerto:' . $percAcertoCp . '%</h4>                            
                            <h4>Total Operações VD <span class="badge bg-secondary">' . $value['total_operacoes_vd'] . '</span> G - ' . $value['total_operacoes_vd_gain'] . ' | L -' . $value['total_operacoes_vd_loss'] . ' | Acerto:' . $percAcertoVd . '%</h4>
                            <h4>Resultado Operações <span class="badge bg-secondary">' . $resultado.' </h4>
                            <h4>Resultado Inverso Operações <span class="badge bg-secondary">' . $resultadoInverso.' </h4>
                        </div>
                    </div>
                </div>';


        if ($i == 2) {
            $card .= '</div><br>';
            $i = 0;
        }

        $i++;
    }


    $card .= '</div>';
    echo ($card);
}
?>