<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Teste</title>
	<style>
		table {
			font-family: arial, sans-serif;
			border-collapse: collapse;
			width: 30%;
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

	$stmt = $conn->prepare("select * from tbl_hist_papel where nm_papel = 'BBDC4' and ds_periodicidade = 'D'");

	$stmt->execute();

	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

	//echo json_encode($results);
	//print_r($results);

	echo "<br>";
	echo "<br>";

	$table = "
	<table border='1'>
		<tr>
			<th>Papel</th>
			<th>Data Pregão</th>
		</tr>";

	foreach ($results as $key => $result) {

		/*foreach ($result as $key => $dados) {

			echo($key)." = ".$dados."<br>";
		}*/
		//var_dump($result);
		$table .= "
		<tr>
			<td>" . $result['nm_papel'] . "</td>
			<td>" . $result['dt_pregao'] . "</td>
		</tr>";

		//echo($result['nm_papel']) ;

		//echo "<br>";
		//echo "=========================================";
		//echo "<br>";
	}

	$table .= "</table>";

	echo $table;



	?>

</body>

</html>