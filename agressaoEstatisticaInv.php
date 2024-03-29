<?php
require_once('util.php');
require_once('sql.php');
$vlOperacaoGain = 0;
$vlOperacaoLoss = 0;
$nrContratos = 0;
$nmPapel = '';
$dsVariacao = '';
$dtOperacaoIni = '';
$dtOperacaoFim = '';
$hrOperacao = '';
$nrIntervalo = 0;
$minPercentual = 0;
$minOperacoes = 0;
$nrRepeticoes = 0;
$idCompraVenda = 'T';

$listaPapeisVariacao = retornaPapelVariacao();

if (isset($_POST['submit'])) {

    $dtOperacaoIni = $_POST['dtInicial'];
    $dtOperacaoFim = $_POST['dtFinal'];
    $idPapelVariacao = $_POST['idPapelVariacao'];

    $nmPapel    =  retornaPapel($idPapelVariacao);
    $dsVariacao =  retornaVariacao($idPapelVariacao);

    $hrOperacao = $_POST['hrOperacao'];
    $nrIntervalo = $_POST['nrIntervalo'];
    $minPercentual = $_POST['minPercentual'];
    $minOperacoes = $_POST['minOperacoes'];
    $nrRepeticoes = $_POST['nrRepeticoes'];
    $idCompraVenda = $_POST['idCompraVenda'];
    $idDebug = $_POST['idDebug'];
    $nrContratos = $_POST['nrContratos'];

    $nrVariacao = substr($dsVariacao,0,strpos($dsVariacao,'P')) ;
    
    $renko = 'N';
    
    if(empty($nrVariacao)){
        $nrVariacao = substr($dsVariacao,0,strpos($dsVariacao,'R')) ;
        $renko = 'S';
    }
   
    
    $tiks = $nmPapel=='WINFUT'?5:0.5;
    $pontos = $nrVariacao * $tiks;

    if($renko == 'N'){
      $vlOperacaoGain = $nmPapel=='WINFUT'? ($pontos * $nrContratos * 0.2)  : ($pontos * $nrContratos * 10);
      $vlOperacaoLoss = $nmPapel=='WINFUT'? ($pontos * $nrContratos * 0.2)  : ($pontos * $nrContratos * 10);
    }else{
      $vlOperacaoGain = $nmPapel=='WINFUT'? ($pontos * $nrContratos * 0.2)  : ($pontos * $nrContratos * 10);
      $vlOperacaoLoss = $nmPapel=='WINFUT'? (($pontos * 2) * $nrContratos * 0.2)  : ($pontos * $nrContratos * 10);
    }
}

$sql = new Sql();

$conn = $sql->retornaPdo();

$stmt = $conn->prepare("
select *
      ,abs(tbl.vl_saldo)vl_saldo_abs
  from tbl_agressao tbl
where tbl.dt_operacao between :dt_operacao_ini and :dt_operacao_fim 
  and tbl.nm_papel = :nm_papel
  and tbl.ds_variacao = :ds_variacao
  and to_number(substr(tbl.hr_operacao,1,2)) < :hr_operacao
order by tbl.dt_operacao desc, tbl.id_reg desc
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

$faixa = $nrIntervalo;
$repeticoes = $nrRepeticoes;
   
for ($i = 0; $i <= $repeticoes; $i++) {

    if ($i == 0) {
        # code...
        $vl_minimo = 0;
        $vl_maximo = ($faixa - 1);
        $titulo = 'Barras Saldo ' . $vl_minimo . ' - ' . $vl_maximo;
    } else {
        # code...
        $vl_minimo = ($i * $faixa);
        $vl_maximo = ($i * $faixa) + ($faixa - 1);
        $titulo = 'Barras Saldo ' . $vl_minimo . ' - ' . $vl_maximo;
    }
    

    array_push($filtro, array('titulo' => $titulo, 'vl_minimo' => $vl_minimo, 'vl_maximo' => $vl_maximo));
}


//Array que irá armazenar os resultados
$resultado = array();
$resultadoDiario = array();


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
                            array_push($resultadoDiario,array('intervalo' =>  $value['titulo'], 'dtOperacao' => $sql['dt_operacao'] ,'operacao' =>'CP' ,'gain' => 1, 'loss' => 0 ));
    
                    } else {
                        $debug .= ' LOSS ';
                        $contadorOperacoesCpLoss++;                    
                        array_push($resultadoDiario,array('intervalo' =>  $value['titulo'], 'dtOperacao' => $sql['dt_operacao'] ,'operacao' =>'CP' ,'gain' => 0, 'loss' => 1 ));
                    }
    
                    $gatilho = '';


                break;
            case 'VD':  // Venda

                if ($sql['vl_fechamento'] < $sql['vl_abertura']) {
                    $debug .= ' GAIN ';
                    $contadorOperacoesVdGain++;
                    array_push($resultadoDiario,array('intervalo' =>  $value['titulo'], 'dtOperacao' => $sql['dt_operacao'] ,'operacao' =>'VD' ,'gain' => 1, 'loss' => 0 ));
                } else {
                    $debug .= ' LOSS ';
                    $contadorOperacoesVdLoss++;
                    array_push($resultadoDiario,array('intervalo' =>  $value['titulo'], 'dtOperacao' => $sql['dt_operacao'] ,'operacao' =>'VD' ,'gain' => 0, 'loss' => 1 ));
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

            if ($sql['vl_saldo'] < 0) {
                $contadorBarrasSaldoPos++;

                $debug .= ' saldo pos <strong>' . $sql['vl_saldo'] . '</strong>';

                if ($idCompraVenda == 'C' || $idCompraVenda == 'T') {
                    if ($sql['vl_fechamento'] > $sql['vl_abertura']   ) {
                        $contadorBarrasPosSaldoPos++;
                        $debug .= ' barra pos';
    
                        if (empty($gatilho) ) {
                            $gatilho = 'CP';
                            $debug .= '<span style="background-color:#31d65d;"><strong> GATILHO ' . $gatilho . '</strong></span>';
                            $contadorOperacoesCp++;
                        }
                    } else {
                        $contadorBarrasPosSaldoNeg++;
                        $debug .= ' barra neg';
                    }
                }



            } else {

                $contadorBarrasSaldoNeg++;

                $debug .= ' saldo neg <strong>' . $sql['vl_saldo'] . '</strong>';

                if ($idCompraVenda == 'V' || $idCompraVenda == 'T') {

                    if ($sql['vl_fechamento'] > $sql['vl_abertura']) {
                        $contadorBarrasNegSaldoPos++;
                        $debug .= ' barra pos';
                    } else {
                        $contadorBarrasNegSaldoNeg++;
                        $debug .= ' barra neg';
    
    
                        if (empty($gatilho) ) {
                            $gatilho = 'VD';
                            $contadorOperacoesVd++;
                            $debug .= '<span style="background-color:#db3a1a;color:white;"><strong> GATILHO ' . $gatilho . '</strong></span>';
                        }
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
                        <label for="idPapelVariacao" class="col-sm-2 col-form-label">Papel / Variação</label>
                        <div class="col-sm-2">
                            <select class="form-select" aria-label="Default select example" id="idPapelVariacao" name="idPapelVariacao">
                                <?php
                                foreach ($listaPapeisVariacao as $key => $papel) {   

                                    $select = '' ;                               

                                    if ($papel['papel'] == $_POST['idPapelVariacao']) {
                                        $select = 'selected' ;                               
                                    }
                                    echo '<option '.$select.' value="'.$papel['papel'].'">'.$papel['papel'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="hrOperacao" class="col-sm-2 col-form-label">Max Horário</label>
                        <div class="col-sm-2">
                          <input type="number" min="10" max="17" class="form-control" id="hrOperacao" name="hrOperacao" value="<?php echo empty($hrOperacao)? 10 : $hrOperacao; ?>">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="nrIntervalo" class="col-sm-2 col-form-label">Intervalo</label>
                        <div class="col-sm-2">
                          <input type="number" min="1"  class="form-control" id="nrIntervalo" name="nrIntervalo" value="<?php echo empty($nrIntervalo)? 1000 : $nrIntervalo; ?>">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="minPercentual" class="col-sm-2 col-form-label">Mínimo Percentual%</label>
                        <div class="col-sm-2">
                          <input type="number" min="1"  class="form-control" id="minPercentual" name="minPercentual" value="<?php echo empty($minPercentual)? 70 : $minPercentual; ?>">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="minOperacoes" class="col-sm-2 col-form-label">Mínimo Operações</label>
                        <div class="col-sm-2">
                          <input type="number" min="1"  class="form-control" id="minOperacoes" name="minOperacoes" value="<?php echo empty($minOperacoes)? 1 : $minOperacoes; ?>">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="nrRepeticoes" class="col-sm-2 col-form-label">Repetições</label>
                        <div class="col-sm-2">
                          <input type="number" min="1"  class="form-control" id="nrRepeticoes" name="nrRepeticoes" value="<?php echo empty($nrRepeticoes)? 80 : $nrRepeticoes; ?>">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="idCompraVenda" class="col-sm-2 col-form-label">Compra / Venda</label>
                        <div class="col-sm-2">
                            <select class="form-select" id="idCompraVenda" name="idCompraVenda">
                                <option <?php if($idCompraVenda == 'C'){ ?> selected <?php } ?>  value="C">Compra</option>
                                <option <?php if($idCompraVenda == 'V'){ ?> selected <?php } ?>  value="V">Venda</option>
                                <option <?php if($idCompraVenda == 'T'){ ?> selected <?php } ?>  value="T">Compra e Venda</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="nrContratos" class="col-sm-2 col-form-label">Contratos</label>
                        <div class="col-sm-2">
                          <input type="number" min="1"  class="form-control" id="nrContratos" name="nrContratos" value="<?php echo empty($nrContratos)? 1 : $nrContratos; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3 row">
                        <label for="idDebug" class="col-sm-2 col-form-label">Debug?</label>
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
        retornaCard($resultado,$vlOperacaoGain,$vlOperacaoLoss,$minPercentual,$minOperacoes);
        ?>


    </div>

</body>

</html>

<?php

function retornaCard($resultado,$vlOperacaoGain,$vlOperacaoLoss,$minPercentual,$minOperacoes)
{

    $i = 1;
    $card = '';
    $estatistica = array();

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

        $resultadoFinanPos = ($value['total_operacoes_cp_gain'] + $value['total_operacoes_vd_gain']) * $vlOperacaoGain;
        $resultadoFinanNeg = ($value['total_operacoes_cp_loss'] + $value['total_operacoes_vd_loss']) * $vlOperacaoLoss;

        
        $resultadoFinanPosInv = ($value['total_operacoes_cp_loss'] + $value['total_operacoes_vd_loss']) * $vlOperacaoLoss;
        $resultadoFinanNegInv = ($value['total_operacoes_cp_gain'] + $value['total_operacoes_vd_gain']) * $vlOperacaoGain;

        $resultadoFinanTotal = $resultadoFinanPos - $resultadoFinanNeg;
        $resultadoFinanTotalInverso = $resultadoFinanPosInv -$resultadoFinanNegInv ;

        $resultadoInverso = ( $value['total_operacoes_cp_loss'] -  $value['total_operacoes_cp_gain']) + ($value['total_operacoes_vd_loss']  - $value['total_operacoes_vd_gain']);

        if ($percAcertoCp >= $minPercentual && $value['total_operacoes_cp'] >= $minOperacoes) {
            array_push($estatistica,array('intervalo' => $value['titulo']
                                         ,'operacao' => 'Compra'
                                         ,'percentual' => $percAcertoCp
                                         ,'operacoes' => $value['total_operacoes_cp']
                                         )
                      );
        }

        if ($percAcertoCp <= (100 - $minPercentual) && $value['total_operacoes_cp'] >= $minOperacoes) {
            array_push($estatistica,array('intervalo' => $value['titulo']
                                         ,'operacao' => 'Compra'
                                         ,'percentual' => $percAcertoCp
                                         ,'operacoes' => $value['total_operacoes_cp']
                                         )
                      );
        }

        if ($percAcertoVd >= $minPercentual &&  $value['total_operacoes_vd'] >= $minOperacoes) {
            array_push($estatistica,array('intervalo' => $value['titulo']
                                         ,'operacao' => 'Venda'
                                         ,'percentual' => $percAcertoVd
                                         ,'operacoes' => $value['total_operacoes_vd']
                                         )
                      );
        }

        if ($percAcertoVd <= (100 - $minPercentual) &&  $value['total_operacoes_vd'] >= $minOperacoes) {
            array_push($estatistica,array('intervalo' => $value['titulo']
                                         ,'operacao' => 'Venda'
                                         ,'percentual' => $percAcertoVd
                                         ,'operacoes' => $value['total_operacoes_vd']
                                         )
                      );
        }


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
                            <h4>Resultado Operações <span class="badge bg-secondary">' . $resultado.' </span> R$ '. $resultadoFinanTotal  .' </h4>
                            <h4>Resultado Inverso Operações <span class="badge bg-secondary">' . $resultadoInverso.' </span> R$ '. $resultadoFinanTotalInverso  .' </h4>
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

    imprimeTabelaEstatistica($estatistica);
    
    echo ($card);
}

echo imprimeResultadoDiario($resultadoDiario,$nmPapel,$nrContratos,$vlOperacaoGain ,$vlOperacaoLoss );

function imprimeTabelaEstatistica($estatistica){
    $table = '<table class="table table-striped">
    <thead>
      <tr>
        <th scope="col">Intervalo</th>
        <th scope="col">Operação</th>
        <th scope="col">Percentual</th>
        <th scope="col">Operações</th>
      </tr>
    </thead>
    <tbody>';

    foreach ($estatistica as $key => $value) {
        $table .='
          <tr>
            <td>'.$value['intervalo'].'</td>
            <td>'.$value['operacao'].'</td>
            <td>'.$value['percentual'].'%</td>
            <td>'.$value['operacoes'].'</td>
          </tr>'; 
    }


    $table .='
    </tbody>
  </table>'; 

  echo $table;

}

function imprimeResultadoDiario($resultadoDiario,$nmPapel,$nrContratos,$vlOperacaoGain ,$vlOperacaoLoss ){

    $gain = 0;
    $loss = 0;
    $dadosCompilados = array(); 
    $i = 1;
    $auxdtOperacao = '';
    $auxIntervalo = '';

    foreach ($resultadoDiario as $key => $value) {

        if ($i == 1) {
            $auxdtOperacao = $value['dtOperacao'];
            $auxIntervalo = $value['intervalo'];   
        }

        if ( ($auxdtOperacao != $value['dtOperacao']) || ($auxIntervalo != $value['intervalo']) ) {
           // die($auxdtOperacao. ' '.$value['dtOperacao']);
            array_push($dadosCompilados,array('intervalo' => $auxIntervalo, 'dtOperacao' => $auxdtOperacao,'Gain' => $gain,'Loss' => $loss));
            $gain = 0;
            $loss = 0;
        }

        $gain += $value['gain'];
        $loss += $value['loss'];
        $i++;
        $auxdtOperacao = $value['dtOperacao'];
        $auxIntervalo = $value['intervalo'];   
    }
    array_push($dadosCompilados,array('intervalo' => $auxIntervalo,'dtOperacao' => $auxdtOperacao,'Gain' => $gain,'Loss' => $loss));
   
    
    return imprimetabela($dadosCompilados,$nmPapel,$nrContratos,$vlOperacaoGain ,$vlOperacaoLoss);
}

function imprimeTabela($array,$nmPapel,$nrContratos,$vlOperacaoGain ,$vlOperacaoLoss){

    $i = 1;
    $auxIntervalo = '';
    $html = '<div class="container">';

    $operacoes = 0;
    $operacoesTotal = 0;
    $custosTotal = 0;
    $custos = 0;

    $resultado = 0;
    $resultadoTotal = 0;

    $resultadoInverso = 0;
    $resultadoLiquidoTotal = 0;

    $resultadoInversoTotal = 0;
    $resultadoLiquidoInvTotal = 0;

    foreach ($array as $key => $value) {

        $operacoes = $value['Gain'] + $value['Loss'];
        $custos = retornaCustos($nmPapel,$operacoes,$nrContratos);  

        $resultadoFinanPos = $value['Gain'] * $vlOperacaoGain;
        $resultadoFinanNeg = $value['Loss'] * $vlOperacaoLoss;

        $resultadoFinanPosInv = $value['Loss'] * $vlOperacaoLoss;
        $resultadoFinanNegInv = $value['Gain'] * $vlOperacaoGain;

        $resultadoFinanTotal = $resultadoFinanPos - $resultadoFinanNeg;
        $resultadoFinanTotalInv = $resultadoFinanPosInv - $resultadoFinanNegInv;

        $resultado = $value['Gain'] - $value['Loss'];
        $resultadoInverso = $value['Loss'] - $value['Gain'];

        $resultadoLiquido = ($resultadoFinanTotal) - $custos;
        $resultadoLiquidoInv = ($resultadoFinanTotalInv) - $custos;

        if ($i == 1 || $auxIntervalo <>  $value['intervalo'] ) {

            if ($i > 1) {

                $html .= '<tr>
                    <th scope="row">Total</th>
                    <th>'.$operacoesTotal.'</th>
                    <th>R$'.$custosTotal.'</th>
                    <th>'.$resultadoTotal.'</th>
                    <th>R$'.$resultadoLiquidoTotal.'</th>
                    <th>'.$resultadoInversoTotal.'</th>
                    <th>R$'.$resultadoLiquidoInvTotal.'</th>
                    </tr> ';

                $html .= ' </tbody></table>';
                $operacoesTotal = 0;
                $custosTotal = 0;
                $resultadoTotal = 0;
                $resultadoInversoTotal = 0;
                $resultadoLiquidoTotal = 0;
                $resultadoLiquidoInvTotal = 0;
            }

            $auxIntervalo = $value['intervalo'];
            $html .= '<div class="alert alert-primary" role="alert">'.$value['intervalo'].'</div>';

            $html .= '<table class="table ">
                        <thead>
                        <tr>
                            <th scope="col">Dt. Pregão</th>
                            <th scope="col">Operações</th>
                            <th scope="col">Custo</th>
                            <th scope="col">Resultado</th>
                            <th scope="col">R$ Líquido</th>
                            <th scope="col">Resultado Inverso</th>
                            <th scope="col">R$ Líquido</th>
                        </tr>
                    </thead><tbody>';
        }

        $style1 = $resultadoLiquido < 0 ? "background-color:#f26f6f;color:white":"background-color:#5ef7ad;";
        $style2 = $resultadoLiquidoInv < 0 ? "background-color:#f26f6f;color:white":"background-color:#5ef7ad;";
        if ($resultado < 0) {            
           // $style = "background-color:#f26f6f;color:white";
        }else{
           // $style = "background-color:#5ef7ad;";
        }

        $html .= ' <tr>
                    <td scope="row">'.retornaDataBr($value['dtOperacao']).'</td>
                    <td>'.$operacoes.'</td>
                    <td>'.str_replace('.',',',$custos).'</td>
                    <td style="'. $style1.'">'.$resultado.'</td>
                    <td style="'. $style1.'">'. str_replace('.',',',$resultadoLiquido) .'</td>
                    <td style="'. $style2.'">'.$resultadoInverso.'</td>
                    <td style="'. $style2.'">'.str_replace('.',',',$resultadoLiquidoInv) .'</td>
                    </tr> 
            ';

        $auxIntervalo = $value['intervalo'];

        $operacoesTotal += $operacoes;
        $custosTotal += $custos;
        $resultadoTotal += $resultado;
        $resultadoInversoTotal += $resultadoInverso;
        $resultadoLiquidoTotal += $resultadoLiquido;
        $resultadoLiquidoInvTotal += $resultadoLiquidoInv;
        $i++;
    }
 

 $html .= '<tr>
                <th scope="row">Total</th>
                <th>'.$operacoesTotal.'</th>
                <th>R$'.$custosTotal.'</th>
                <th>'.$resultadoTotal.'</th>
                <th>'.$resultadoLiquidoTotal.'</th>
                <th>'.$resultadoInversoTotal.'</th>
                <th>'.$resultadoLiquidoInvTotal.'</th>
                </tr> ';
  $html .= ' </tbody></table>'; 
  $html .= '</div>';

  return $html;

}

?>


