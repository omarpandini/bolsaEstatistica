<?php
echo 'teste';

$conn = new PDO("mysql:dbname=bolsav;host=localhost", "root", "admin");

	$stmt = $conn->prepare("insert into tbl_teste(cl_texto)values(:CLTEXTO)");

    $clTexto = utf8_decode('Olá Mundo');

	$stmt->bindParam(":CLTEXTO",$clTexto);

	$stmt-> execute();

	echo "Inserido Ok!";

    //var_dump($results);
?>