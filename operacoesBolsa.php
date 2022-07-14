<!DOCTYPE html>
<html lang="en">



<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
	<title>Teste</title>
	<style>
		table {
			font-family: arial, sans-serif;
			border-collapse: collapse;
			width: 50%;
		}

		td,
		th {
			border: 1px solid #dddddd;
			text-align: left;
			padding: 8px;
		}

		tr:nth-child(even) {
			background-color: #dddddd;
		}
	</style>
</head>

<body>

	<?php

	$conn = new PDO("mysql:dbname=bolsav;host=localhost", "root", "admin");

	$stmt = $conn->prepare("
	
	select t.*
			, DATE_FORMAT(t.dt_operacao,'%d/%m/%Y')dt_operacao_formatada
			,case when t.vl_resultado < 0 then 1 else 0 end negativo
			,case when t.vl_resultado < 0 then 0 else 1 end positivo
		from tbl_operacoes_bolsa  t
		where t.dt_operacao <>  STR_TO_DATE('22/06/2022', '%d/%m/%Y')
		order by t.dt_operacao,t.id_operacoes_bolsa;

	");

	$stmt->execute();

	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo "<br>";
	echo "<br>";

	$resultado = 0;
	$maxNegativo = 0;
	$maxNegativoAux = 0;
	$maxPositivo = 0;
	$maxPositivoAux = 0;

	$table = "
	<table border='1'>
		<tr>
			<th>Papel</th>
			<th>Data Pregão</th>
			<th>Vl. Compra</th>
			<th>Vl. Venda</th>
			<th>Qtd. Contratos</th>
			<th>Resultado Pontos</th>
			<th>Resultado R$</th>
		</tr>";

	foreach ($results as  $result) {

		$resultado = $result['vl_preco_venda'] - $result['vl_preco_compra'];

		$maxPositivo = $result['vl_resultado'];
		$maxNegativo = $result['vl_resultado'];

		if ($maxPositivoAux == 0) {
			$maxPositivoAux = $maxPositivo;
		}

		if ($maxNegativoAux == 0) {
			$maxNegativoAux = $maxNegativo;
		}

		if ($maxPositivo > $maxPositivoAux) {
			$maxPositivoAux = $maxPositivo;
		}

		if ($maxNegativo < $maxNegativoAux) {
			$maxNegativoAux = $maxNegativo;
		}

		if ($resultado < 0) {
			$color = 'red';
			$colorText = 'white';
		} else {
			$color = '#14962d';
			$colorText = 'black';
		}

		$table .= "
		<tr style='background:" . $color	. ";color:" . $colorText . ";'>
			<td>" . $result['nm_papel'] . "</td>
			<td>" . $result['dt_operacao_formatada'] . "</td>
			<td>" . $result['vl_preco_compra'] . "</td>
			<td>" . $result['vl_preco_venda'] . "</td>
			<td>" . $result['qtd_contratos'] . "</td>
			<td>" . $resultado . "</td>
			<td>" . $result['vl_resultado'] . "</td>
		</tr>";
	}

	$table .= "</table>";

	echo $table;

	echo '<br><br>';
	?>


	<div class="bd-example-snippet bd-code-snippet">
		<div class="bd-example">
			<p class="display-1" style="background-color: greenyellow;width: 50%;"><strong> Máximo</strong> <?php echo $maxPositivoAux; ?></p>
			<p class="display-1"  style="background-color: red;color:white;width: 50%;"><strong>Mínimo</strong>  <?php echo $maxNegativoAux; ?></p>
		</div>
	</div>

</body>

</html>