<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//require 'vendor/autoload.php';
include_once 'FelGT.php';
include_once 'prettyPrint.php';

echo "<h1>Testing FEL 1.0</h1>";
/**
 * SE AÑADE AL CONSTRUCTOR UN TERCER PARAMETRO PARA INDICAR EL PROVEEDOR DEL SERVICIO DE FIRMA
 */
//PASO 1: Creacion de objeto
$fel=new alexius\felgt\FelGT("./","", \alexius\felgt\FelGT::PROVIDER_FORCON,false);
$fel->setTesting();
//PASO 2: Se colocan datos generales de la factura
$fel->setDatosGenerales(\alexius\felgt\FelGT::DOCUMENTO_FACTURA, false, '2020-11-18T00:00:00-06:00',"GTQ");

//PASO 3: Datos de emisor
$fel->setDatosEmisor("85741191", "PROLEASE, SOCIEDAD ANONIMA", "1", "PROLEASE", 
        "sramirez@alexius.net", "GEN");
$fel->setDireccionEmisor("Ciudad 00-00 Ciudad Zona: 0, Guatemala, Guatemala", 
        "0", "Guatemala", "Guatemala", "GT");

//PASO 4: Datos de receptor
$fel->setDatosReceptor("CF","Consumidor Final", "sramirez@alexius.net",false);
$fel->setDireccionReceptor("Ciudad 00-00 Ciudad Zona: 0, Guatemala, Guatemala", 
        "0", "Guatemala", "Guatemala", "GT");

/**
 * PASO 5: AÑADIR LINEAS
 * INICIA EL AÑADIR ITEMS
 * Esto deberia ser un foreach por cada linea de la factura
 */

    $item=new alexius\felgt\Item(1, 10, "CERVEZA DE BOTELLA", 100.00, 1000.00, 1072.00);
    //$item->addDescuento(1);
    //Se añaden los impuestos de la linea
    $item->addImpuesto("IVA",1,107.14,892.86);
    $item->addImpuesto("BEBIDAS ALCOHOLICAS",1,72,5,240);

    //Se añade el item al objeto Fel
    $fel->agregarItem($item);
/*
 * Termina el añadir items
 */

//PASO 6: Se agregan los impuestos globales
$fel->agregarImpuesto("IVA", 107.14);
$fel->agregarImpuesto("BEBIDAS ALCOHOLICAS", 72.00);

//PASO 7: Se añade el gran total
$fel->setGranTotal(1072);

//PASO 8: Se envia a certificar
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
        <td><textarea rows="20"><?php echo $fel->jsonFirma; ?></textarea></td>
    </tr>
    <tr><td colspan="4" style="font-size: 1.2em;font-weight: bold;text-align: center">CERTIFICACION ENVIADO</td></tr>
    <tr>
        <td><textarea rows="20"><?php echo $fel->xmlFirmado; ?></textarea></td>
        <td><textarea rows="20"><?php echo $fel->body; ?></textarea></td>
    </tr>
    <tr><td colspan="4" style="font-size: 1.2em;font-weight: bold;text-align: center">CERTIFICACION RECIBIDO</td></tr>
    <tr>
        <td><textarea rows="20"><?php echo $fel->xmlCertificado; ?></textarea></td>
        <td><textarea rows="20"><?php echo $fel->jsonCertificacion; ?></textarea></td>
    </tr>
</table>