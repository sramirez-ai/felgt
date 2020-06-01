<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';
include_once 'FelGT.php';
include_once 'prettyPrint.php';

echo "<h1>Testing FEL 1.0</h1>";

$fel=new alexius\felgt\FelGT("./","",false);
$fel->setTesting();
$fel->setDatosGenerales(\alexius\felgt\FelGT::DOCUMENTO_FACTURA, false, '2020-05-27T00:00:00-06:00',"GTQ");
$fel->setDatosEmisor("1000000000K", "Usuario de Pruebas IT", "1", "Usuario de Pruebas IT", 
        "sramirez@alexius.net", "GEN");
$fel->setDireccionEmisor("Ciudad 00-00 Ciudad Zona: 0, Guatemala, Guatemala", 
        "0", "Guatemala", "Guatemala", "GT");
//$fel->setDatosReceptor("31590462","Ovidio Lopez", "sramirez@alexius.net",false);
$fel->setDatosReceptor("CF","Consumidor Final", "sramirez@alexius.net",false);
$fel->setDireccionReceptor("Ciudad 00-00 Ciudad Zona: 0, Guatemala, Guatemala", 
        "0", "Guatemala", "Guatemala", "GT");

$item=new alexius\felgt\Item(1, 2, "Calcetines Con Rombos", 2.52, 5.04, 5.04);
//$item->addDescuento(1);
$item->addImpuesto("IVA",1,0.54,4.5);

$fel->agregarItem($item);

$fel->agregarImpuesto("IVA", 0.54);

$fel->setGranTotal(5.04);

$fel->certificar("TREBOL0151236845","samuelramirez@outlook.com");

echo "<strong>UUID:".$fel->uuid."</strong><br>";
echo "<strong>Serie:".$fel->serie."</strong><br>";
echo "<strong>Numero:".$fel->numero."</strong><br>";

?>
<style>
    textArea{
        width: 100%;
    }
    table,td{
        border: black 1px solid;
    }
    
</style>
<br><br>
<table width="100%">
    <tr style="font-size: 1.5em;font-weight: bold;text-align: center"><td width="25%">XML</td><td width="25%">JSON</td></tr>
    <tr><td colspan="4" style="font-size: 1.2em;font-weight: bold;text-align: center">FIRMA ENVIADO</td></tr>
    <tr>
        <td><textarea rows="20"><?php echo $fel->xml; ?></textarea></td>
        <td><textarea rows="20"><?php echo prettyPrint($fel->bodyFirma); ?></textarea></td>
    </tr>
    <tr><td colspan="4" style="font-size: 1.2em;font-weight: bold;text-align: center">FIRMA RECIBIDO</td></tr>
    <tr>
        <td><textarea rows="20"><?php echo $fel->xmlFirmado; ?></textarea></td>
        <td><textarea rows="20"><?php echo prettyPrint($fel->jsonFirma); ?></textarea></td>
    </tr>
    <tr><td colspan="4" style="font-size: 1.2em;font-weight: bold;text-align: center">CERTIFICACION ENVIADO</td></tr>
    <tr>
        <td><textarea rows="20"><?php echo $fel->xmlFirmado; ?></textarea></td>
        <td><textarea rows="20"><?php echo prettyPrint($fel->body); ?></textarea></td>
    </tr>
    <tr><td colspan="4" style="font-size: 1.2em;font-weight: bold;text-align: center">CERTIFICACION RECIBIDO</td></tr>
    <tr>
        <td><textarea rows="20"><?php echo $fel->xmlFirmado; ?></textarea></td>
        <td><textarea rows="20"><?php echo prettyPrint($fel->jsonCertificacion); ?></textarea></td>
    </tr>
</table>