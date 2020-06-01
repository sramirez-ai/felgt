# felgt

Uso:

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
$fel->jsonCertificacion;
