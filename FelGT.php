<?php

namespace alexius\felgt;
/*
 * PRIMER INTENTO CON GREENTER... FALLIDO
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;*/

/*
 * SEGUNDO INTENTO CON XMLSigner... FALLIDO
use Selective\XmlDSig\DigestAlgorithmType;
use Selective\XmlDSig\XmlSigner;
 */

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;


use DOMDocument;


/**
 * Clase que agrupa los campos necesarios para certificar una factura FEL
 * 
 * USO:
 * Luego de crear un nuevo objeto de este tipo 
 * 1. Llamar la funcion setCredentials con los datos de acceso, ó trabajar en desarrollo con setTesting(true)
 * 2. Establecer los datos generales del DET con el metodo setDatosGenerales(...)
 * 3. Establecer los datos del emisor con el metodo setDatosEmisor(...)
 * 4. Establecer la direccion del emisor con el metodo setDireccionEmisor()
 * 5. Establecer los datos del receptor con el metodo setDatosReceptor(...)
 * 6. Establecer la direccion del receptor con el metodo setDireccionReceptor(...)
 *
 * @author Samuel Ramirez - 28/04/2020
 * @version 1.0
 */
class FelGT {

    /**
     * Usuario para el portal de infile.
     * @var String 
     */
    public $usuario = "";

    /**
     * Llave/Clave/Password para el portal de infile.
     * @var String 
     */
    public $llave = "";

    /**
     * Identificador de cliente para el portal de infile.
     * @var String 
     */
    public $identificador = "";

    /**
     * Variable que establece si los mensajes del log ERROR se deben enviar como
     * Exceptions de php 
     */
    public $exceptions = false;

    /**
     * Log de la clase, util para conocer el proceso hecho por 
     * las peticiones.
     * @var Array 
     */
    private $log = [];

    const LOG_LEVEL_WARNING = 1;
    const LOG_LEVEL_DEBUG = 2;
    const LOG_LEVEL_ERROR = 0;

    /**
     * Niveles de Log. ERROR | WARNING | DEBUG
     * @var Array 
     */
    protected static $log_level = [
        self::LOG_LEVEL_ERROR, self::LOG_LEVEL_WARNING, self::LOG_LEVEL_DEBUG
    ];

    //URL
    const URL_CERTIFICACION = 'https://certificador.feel.com.gt/fel/certificacion/v2/dte/';
    const URL_ANULACION = 'https://certificador.feel.com.gt/fel/anulacion/v2/dte/';
    const URL_FIRMA = 'https://signer-emisores.feel.com.gt/sign_solicitud_firmas/firma_xml';
    const CONTENT_TYPE_JSON = 'application/json';
    const DATE_FORMAT = '%y-%m-%dT%';
    const NIT_REGEX = "/(([1-9])+([0-9])*([0-9]|K))$/";
    const EMAIL_REGEX = "/((\w[-+._\w]+@\w[-.\w]+\.\w[-.\w]+)(;?))*/";
    const DOCUMENTO_FACTURA = 'FACT';
    const DOCUMENTO_FACTURA_CAMBIARIA = 'FCAM';
    const DOCUMENTO_FACTURA_PEQUENO_CONTRIBUYENTE = 'FPEQ';
    const DOCUMENTO_FACTURA_CAMBIARIA_PEQUENO_CONTRIBUYENTE = 'FCAP';
    const DOCUMENTO_FACTURA_ESPECIAL = 'FESP';
    const DOCUMENTO_NOTA_ABONO = 'NABN';
    const DOCUMENTO_REDENCION = 'RDON';
    const DOCUMENTO_RECIBO = 'RECI';
    const DOCUMENTO_NOTA_DEBITO = 'NDEB';
    const DOCUMENTO_NOTA_CREDITO = 'NCRE';
    const SIGNATURE_SHA256_URL = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    const DIGEST_SHA256_URL = 'http://www.w3.org/2001/04/xmlenc#sha256';

    /**
     * Tipos de DTE
     */
    protected static $tipos_dte = [
        self::DOCUMENTO_FACTURA,
        self::DOCUMENTO_FACTURA_CAMBIARIA,
        self::DOCUMENTO_FACTURA_PEQUENO_CONTRIBUYENTE,
        self::DOCUMENTO_FACTURA_CAMBIARIA_PEQUENO_CONTRIBUYENTE,
        self::DOCUMENTO_FACTURA_ESPECIAL,
        self::DOCUMENTO_NOTA_ABONO,
        self::DOCUMENTO_REDENCION,
        self::DOCUMENTO_RECIBO,
        self::DOCUMENTO_NOTA_DEBITO,
        self::DOCUMENTO_NOTA_CREDITO,
    ];
    /**
     * Tipos de Impuestos aceptados por la SAT 
     */
    protected static $tipo_impuesto = [
        "IVA", "PETROLEO", "TURISMO HOSPEDAJE",
        "TURISMO PASAJES", "TIMBRE DE PRENSA", "BOMBEROS",
        "TASA MUNICIPAL", "BEBIDAS ALCOHOLICAS", "TABACO",
        "CEMENTO", "BEBIDAS NO ALCOHOLICAS", "TARIFA PORTUARIA",
    ];
    
    /**
     * SEGUN ISO 4217
     * @var array Arreglo con las monedas aceptadas por la SAT
     */
    protected static $monedas = [
        "GTQ", "USD", "VES", "CRC", "SVC",
        "NIO", "DKK", "NOK", "SEK", "CAD", "HKD", "TWD",
        "PTE", "EUR", "CHF", "HNL", "GBP", "ARS", "DOP",
        "COP", "MXN", "BRL", "MYR", "INR", "PKR", "KPW", "JPY"
    ];

    /**
     * Código de País ISO 3166-1
     */
    protected static $paises = [
        "AD", "AE", "AF", "AG", "AI", "AL", "AM", "AN",
        "AO", "AQ", "AR", "AS", "AT", "AU", "AW", "AX",
        "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH",
        "BI", "BJ", "BL", "BM", "BN", "BO", "BR", "BS",
        "BT", "BV", "BW", "BY", "BZ", "CA", "CC", "CD",
        "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN",
        "CO", "CR", "CU", "CV", "CX", "CY", "CZ", "DE",
        "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG",
        "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM",
        "FO", "FR", "GA", "GB", "GD", "GE", "GF", "GG",
        "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR",
        "GS", "GT", "GU", "GW", "GY", "HK", "HM", "HN",
        "HR", "HT", "HU", "ID", "IE", "IL", "IM", "IN",
        "IO", "IQ", "IR", "IS", "IT", "JE", "JM", "JO",
        "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KP",
        "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI",
        "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA",
        "MC", "MD", "ME", "MF", "MG", "MH", "MK", "ML",
        "MM", "MN", "MO", "MP", "MQ", "MR", "MS", "MT",
        "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC",
        "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR",
        "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH",
        "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW",
        "PY", "QA", "RE", "RO", "RS", "RU", "RW", "SA",
        "SB", "SC", "SD", "SE", "SG", "SH", "SI", "SJ",
        "SK", "SL", "SM", "SN", "SO", "SR", "ST", "SV",
        "SY", "SZ", "TC", "TD", "TF", "TG", "TH", "TJ",
        "TK", "TL", "TM", "TN", "TO", "TR", "TT", "TV",
        "TW", "TZ", "UA", "UG", "UM", "US", "UY", "UZ",
        "VA", "VC", "VE", "VG", "VI", "VN", "VU", "WF",
        "WS", "YE", "YT", "ZA", "ZM", "ZW", "BQ", "CW",
        "SS", "SX"
    ];
    

    /**
     * De acuerdo al Régimen que tenga registrado el contribuyente, se refiere a que puede ser General, 
     * Pequeño Contribuyente, Pequeño Contribuyente Electronico, Agropecuario, Agropecuario Electrónico. 
     * (EXE queda por compatibilidad para DTE hasta 29/feb/2020)
     * @var array Arreglo con los tipos de afiliacion aceptadas por la SAT
     */
    protected static $tipo_afiliacion_iva = [
        "GEN", "EXE", "PEQ",
        "PEE", "AGR", "AGE"
    ];
    private $credencialesEstablecidos = false;

    /*     * *****************************
     *      DATOS GENERALES        *
     * ***************************** */
    private $tipo_documento = "";
    private $fechaHoraEmision = "";

    /**
     * Propiedad que indica si el documento es EXPORTACION
     * @var bool
     */
    private $es_exportacion = false;
    private $codigoMoneda = "";
    private $tipoPersoneria = "";
    private $datosGeneralesEstablecidos = false;
    private $xmlDatosGenerales = "";

    /*     * *****************************
     *      DATOS EMISOR        *
     * ***************************** */
    private $nit_emisor = "";
    private $nombre_emisor = "";
    private $codigo_establecimiento = "";
    private $nombre_comercial = "";
    private $correo_emisor = "";
    private $afiliacion_iva_emisor = "";
    private $datosEmisorEstablecidos = false;
    private $xmlDatosEmisor;

    /*     * *****************************
     *      DIRECCION EMISOR       *
     * ***************************** */
    private $direccion_emisor = "";
    private $codigo_postal_emisor = "";
    private $municipio_emisor = "";
    private $departamento_emisor = "";
    private $pais_emisor = "GT";
    private $direccionEmisorEstablecida = false;



    /*     * *****************************
     *      DATOS RECEPTOR        *
     * ***************************** */
    private $idReceptor = "";
    private $nombre_receptor = "";
    private $correo_receptor = "";
    private $es_cui = "";
    private $datosReceptorEstablecidos = false;
    private $xmlDatosReceptor;

    /*     * *****************************
     *      DIRECCION RECEPTOR       *
     * ***************************** */
    private $direccion_receptor = "";
    private $codigo_postal_receptor = "";
    private $municipio_receptor = "";
    private $departamento_receptor = "";
    private $pais_receptor = "";
    private $direccionReceptorEstablecida = false;

    /*     * ************************
     *  ITEMS & TAXES
     * ************************ */
    private $items = [];
    private $taxes = [];
    private $granTotal = 0;

    /**
     * XML
     */
    public $xml = "";
    public $xmlFirmado = "";
    public $xmlCertificado="";
    public $xmlFirmadoBase64 = "";
    public $body = "";
    public $bodyFirma="";
    public $jsonFirma="";
    public $jsonCertificacion="";
    private $base64xml = "";
    public $path = "";
    public $file_name = "";
    public $serie="";
    public $numero="";
    public $uuid="";

    /**
     * Constructor de la clase.
     * @param string $xml_path Indica la ruta donde sera almacenado el archivo 
     * xml certificado
     * @param string $file_name El nombre del archivo a generar
     * @param bool $exceptions Establece si la clase envia las excepciones
     */
    public function __construct($xml_path, $file_name, $exceptions = null) {
        $this->path = $xml_path;
        $this->file_name = $file_name;
        if (null !== $exceptions) {
            $this->exceptions = (bool) $exceptions;
        }
        $this->doLog("Constructor de la clase", self::LOG_LEVEL_DEBUG);
    }

    /**
     * Destructor.
     */
    public function __destruct() {
        
    }

    /**
     * Metodo que coloca las credenciales de acceso a INFILE.
     * 
     * Si la consulta es de prueba utilizar el metodo: setTesting(true);
     * y seran asignadas las credenciales de prueba de INFILE
     * @param String $usuario Usuario de Api FEL
     * @param String $llave llave/clave/password de Api FEL
     * @param String $identificador Identificador del cliente brindado por SAT
     */
    public function setCredentials($usuario, $llave, $identificador) {
        $this->usuario = $usuario;
        $this->llave = $llave;
        $this->identificador = $identificador;
        $this->credencialesEstablecidos = true;
        $this->doLog("Credenciales establecidas", self::LOG_LEVEL_DEBUG);
    }

    public function setTesting($test = true) {
        if ($test) {
            $this->usuario = "DEMO_FEL";
            $this->llave = "E5DC9FFBA5F3653E27DF2FC1DCAC824D";
            $this->identificador = "b21b063dec8367a4d15f4fa6dc0975bc";
            $this->credencialesEstablecidos = true;
            $this->doLog("Apuntando a instancia de TEST", self::LOG_LEVEL_DEBUG);
        } else {
            $this->doLog("Apuntando a instancia de PRODUCCION", self::LOG_LEVEL_DEBUG);
        }
    }

    /**
     * Funcion que establece los datos generales del documento
     * @param String $tipo Tipo de documento segun el array $tipos_dte
     * @param bool $exportacion Inidica si el DTE es de exportacion
     * @param String $fechaHoraEmision Fecha y hora de emisión del DTE. 
     * Formato aaaa-mm-ddThh:mm:ss.000-06:00 (Milisegundos opcionales, 
     * zona horaria especificada o interpretada como hora de Guatemala.)
     * @param String $codigoMoneda Código de la moneda en la que se emite el DTE.
     * @param String $tipoPersoneria OPCIONAL, Indica el tipo de personeria que esta emitiendo, 
     * es requerido para personerias que pueden emitir recibos de donacion.
     * El listado de los tipos de personeria aceptados por la sat pueden verse en el JSON:
     * https://github.com/fel-sat-gob-gt/cat/blob/desa/json/CatalogoRtuTipoPersoneria.json
     * @param Int $numeroAcceso Es un número generado por el Emisor en caso de contingencia, 
     * que va desde 100000000 hasta 999999999 (NO UTLIZADO EN ESTA VERSION)
     */
    public function setDatosGenerales($tipo, $exportacion, $fechaHoraEmision, $codigoMoneda, $tipoPersoneria = null, $numeroAcceso = null) {
        if ($this->isInArray($tipo, self::$tipos_dte)) {
            $this->tipo_documento = $tipo;
            $this->es_exportacion = $exportacion;
            $this->fechaHoraEmision = $fechaHoraEmision;
            if ($this->isInArray($codigoMoneda, self::$monedas)) {
                $this->codigoMoneda = $codigoMoneda;
                if ($tipoPersoneria != null) {
                    $this->tipoPersoneria = $tipoPersoneria;
                }
                $this->datosGeneralesEstablecidos = true;
                $this->doLog("Datos Generales establecidos", self::LOG_LEVEL_DEBUG);
            } else {
                $this->doLog("El tipo de moneda del documento no es valida", self::LOG_LEVEL_ERROR);
            }
        } else {
            $this->doLog("El tipo de documento seleccionado no es valido", self::LOG_LEVEL_ERROR);
        }
    }

    /**
     * Funcion que establece los datos del emisor del documento
     * @param String $nit Indica el NIT del Emisor del DTE (sin guión)
     * se validará mediante el regexp (([1-9])+([0-9])*([0-9]|K))$
     * @param string $nombre Nombres y apellidos o razón social del Emisor 
     * (De acuerdo a los registros tributarios en el momento de la emisión).
     * @param String $codigo_establecimiento Número del establecimiento donde se emite el documento. 
     * Es el que aparece asignado por SAT en sus registros. 
     * @param String $nombre_comercial Indica el nombre comercial del establecimiento 
     * (de acuerdo a los registros tributarios) donde se emite el documento.
     * @param String $correo_emisor Indica la Dirección de correo electrónico del Emisor.
     * Sera validado con el regexp ((\w[-+._\w]+@\w[-.\w]+\.\w[-.\w]+)(;?))*
     * @param String $afilicion_iva De acuerdo al Régimen que tenga registrado el contribuyente, se refiere a que puede ser 
     * General, Pequeño Contribuyente, Pequeño Contribuyente Electronico, Agropecuario, 
     * Agropecuario Electrónico.
     */
    public function setDatosEmisor($nit, $nombre, $codigo_establecimiento, $nombre_comercial, $correo_emisor, $afilicion_iva) {
        if ($this->isNITValid($nit)) {
            $this->nit_emisor = $nit;
            $this->nombre_emisor = $nombre;
            $this->codigo_establecimiento = $codigo_establecimiento;
            $this->nombre_comercial = $nombre_comercial;
            if ($this->isEMAILValid($correo_emisor)) {
                $this->correo_emisor = $correo_emisor;
                if ($this->isInArray($afilicion_iva, self::$tipo_afiliacion_iva)) {
                    $this->afiliacion_iva_emisor = $afilicion_iva;
                    $this->datosEmisorEstablecidos = true;
                    $this->doLog("Datos del Emisor Establecidos", self::LOG_LEVEL_DEBUG);
                } else {
                    $this->doLog("La Afiliacion IVA del emisor no corresponde a ninguna opcion conocida", self::LOG_LEVEL_ERROR);
                }
            } else {
                $this->doLog("El correo del emisor no es valido", self::LOG_LEVEL_ERROR);
            }
        } else {
            $this->doLog("El Nit del emisor no es valido", self::LOG_LEVEL_ERROR);
        }
    }

    /**
     * Funcion que establece la direccion del emisor del documento
     * @param String $direccion Maximo 200 caracteres
     * @param int $codigo_postal Numero entero con el codigo postal
     * @param String $municipio Maximo 100 caracteres
     * @param String $departamento Maximo 100 caracteres
     * @param String $pais Código de País ISO 3166-1 sera validado que pertenezca
     * al arreglo
     */
    public function setDireccionEmisor($direccion, $codigo_postal, $municipio, $departamento, $pais) {
        if ($this->isInArray($pais, self::$paises)) {
            $this->direccion_emisor = $direccion;
            $this->codigo_postal_emisor = $codigo_postal;
            $this->municipio_emisor = $municipio;
            $this->departamento_emisor = $departamento;
            $this->pais_emisor = $pais;
            $this->direccionEmisorEstablecida = true;
            $this->doLog("Direccion del emisor establecida", self::LOG_LEVEL_DEBUG);
        } else {
            $this->doLog("El pais seleccionado (direccion emisor) no pertenece a ningun código de País ISO 3166-1", self::LOG_LEVEL_ERROR);
        }
    }

    /**
     * Funcion que establece los datos del receptor del documento
     * @param String $idReceptor Indica el NIT o CUI del RECEPTOR, CF.
     * @param string $nombre Si la casilla “IDReceptor” contiene un NIT valido el nombre indicado debe corresponder a los registros tributarios. 
     * Caso contrario el contenido puede ser cualquiera que solicite el RECEPTOR. (Maximo 255 caracteres)
     * @param String $codigo_establecimiento Número del establecimiento donde se emite el documento. 
     * Es el que aparece asignado por SAT en sus registros. 
     * @param String $correo Indica la Dirección de correo electrónico del receptor del documento electronico.
     * @param bool $es_cui OPCIONAL Si esto se envia como true, se entiende que el campo idReceptor no lleva un NIT 
     * si no un CUI (CODIGO UNICO DE IDENTIDAD)
     */
    public function setDatosReceptor($idReceptor, $nombre, $correo, $es_cui = false) {
        if ($this->isEMAILValid($correo)) {
            $this->correo_receptor = $correo;
            $this->nombre_receptor = $nombre;
            $this->datosReceptorEstablecidos = true;
            if (!$es_cui && !$this->isNITValid($idReceptor)) {
                $this->doLog("El id del receptor ha sido marcado como NIT pero no es valido", self::LOG_LEVEL_ERROR);
            } else {
                $this->idReceptor = $idReceptor;
                $this->doLog("Datos del receptor Establecidos", self::LOG_LEVEL_DEBUG);
            }
        } else {
            $this->doLog("El correo del receptor no es valido", self::LOG_LEVEL_ERROR);
        }
    }

    /**
     * Funcion que establece la direccion del receptor del documento
     * @param String $direccion Maximo 200 caracteres
     * @param int $codigo_postal Numero entero con el codigo postal
     * @param String $municipio Maximo 100 caracteres
     * @param String $departamento Maximo 100 caracteres
     * @param String $pais Código de País ISO 3166-1 sera validado que pertenezca
     * al arreglo
     */
    public function setDireccionReceptor($direccion, $codigo_postal, $municipio, $departamento, $pais) {
        if ($this->isInArray($pais, self::$paises)) {
            $this->direccion_receptor = $direccion;
            $this->codigo_postal_receptor = $codigo_postal;
            $this->municipio_receptor = $municipio;
            $this->departamento_receptor = $departamento;
            $this->pais_receptor = $pais;
            $this->direccionReceptorEstablecida = true;
            $this->doLog("Direccion del receptor establecida", self::LOG_LEVEL_DEBUG);
        } else {
            $this->doLog("El pais seleccionado (direccion receptor) no pertenece a ningun código de País ISO 3166-1", self::LOG_LEVEL_ERROR);
        }
    }

    /**
     * Añade un item al documento
     * @param Item $item Objeto de la clase \alexius\fetgt\Item con los datos de la linea
     * se validará el tipado, y ademas se verificara que los impuestos de la linea (en caso de existir)
     * correspondan a un impuesto conocido por la SAT
     */
    public function agregarItem(\alexius\felgt\Item $item) {
        if ($item instanceof \alexius\felgt\Item) {
            $valid = true;
            if (count($item->impuestos) > 0) {
                foreach ($item->impuestos as $value) {
                    if (!$this->isInArray($value["nombre_corto"], self::$tipo_impuesto)) {
                        $valid = false;
                        $this->doLog("El impuesto '" . $value["nombre_corto"] . "' no existe como impuesto en el listado de la SAT", self::LOG_LEVEL_ERROR);
                    }
                }
            }
            if ($valid) {
                $this->items[] = $item;
                $this->doLog("Item añadido '" . $item->descripcion . "' (" . $item->cantidad . ")", self::LOG_LEVEL_DEBUG);
            }
        } else {
            $this->doLog("El item que se intenta añadir no es un objeto de la clase Item", self::LOG_LEVEL_ERROR);
        }
    }

    /**
     * Añade una linea al total de impuestos del documento
     * @param string $nombreCorto nombre del impuesto segun la SAT lo establece
     * @param float $montoTotal monto del impuesto a añadir
     */
    public function agregarImpuesto($nombreCorto, $montoTotal) {
        if (!$this->isInArray($nombreCorto, self::$tipo_impuesto)) {
            $this->doLog("El impuesto '" . $nombreCorto . "' no existe como impuesto en el listado de la SAT", self::LOG_LEVEL_ERROR);
        } else {
            $this->taxes[] = ["nombre_corto" => $nombreCorto, "monto_total" => $montoTotal];
            $this->doLog("Impuesto añadido '" . $nombreCorto . "' ", self::LOG_LEVEL_DEBUG);
        }
    }

    /**
     * Establece el Gran Total del documento
     * @param float $granTotal Monto total del documento
     */
    public function setGranTotal($granTotal) {
        $this->granTotal = $granTotal;
    }

    /**
     * Funcion que envia la solicitud a certificar
     * @param string $correoCopia Dirección de envío de correo en copia para el documento certificado (No habilitado actualmente por INFILE)
     */
    public function certificar($codigo_unico, $correoCopia) {
        if ($this->validarCertificacion()) {
            $this->generateXml();
            $this->firmarXML($this->xml,$codigo_unico);
            if($this->xmlFirmado===false){
                $this->logError("Ocurrio un error al firmar el documento.");
            }
            $body = json_encode([
                "nit_emisor" => $this->nit_emisor,
                "correo_copia" => $correoCopia,
                "xml_dte" => $this->xmlFirmadoBase64
            ]);
            $this->body = $body;

            $this->logDebug("BODY: " . $body);
            $this->logDebug("XMLFirmado: 
                " . $this->xmlFirmado);
            
            $ch = curl_init(self::URL_CERTIFICACION);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',"usuario:".$this->usuario,"llave:".$this->llave,"identificador:".$codigo_unico));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $result = curl_exec($ch);
            $this->jsonCertificacion=$result;
            $json= json_decode($result,true);
            
            if($json["resultado"]){
                $this->xmlCertificado=$json["xml_certificado"];
                $this->uuid=$json["uuid"];
                $this->serie=$json["serie"];
                $this->numero=$json["numero"];
            }else{
                $this->logError("Ocurrieron errores por favor verifique la propiedad jsonCertificacion para los detalles");
            }
            
            
        } else {
            $this->logError("El documento actual no cumple la validacion previa realizada por la clase, por favor verifique el log");
        }
    }

    public function firmarXML($xml,$codigo_unico,$anulacion=false){
            $body = json_encode([
                "llave" => $this->identificador,
                "archivo" => base64_encode($xml),
                "codigo" =>$codigo_unico,
                "alias" =>"DEMO_FEL",
                "es_anulacion" =>($anulacion?"Y":"N")
            ]);
            $this->bodyFirma=$body;
            $ch = curl_init(self::URL_FIRMA);
            // Set the content type to application/json
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

            // Return response instead of outputting
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            // Execute the POST request
            $result = curl_exec($ch);
            $this->jsonFirma=$result;
            $json= json_decode($result,true);
            $this->logDebug("Resultado de firma: ".$result);
            if($json["resultado"]){
                $this->xmlFirmadoBase64= $json["archivo"];
                $this->xmlFirmado=base64_decode($json["archivo"]);
            }else{
                $this->xmlFirmado=false;
                $this->xmlFirmadoBase64=false;
            }
            
    }
    
    private function generateXml() {

        $xml = '<dte:GTDocumento xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1" xsi:schemaLocation="http://www.sat.gob.gt/dte/fel/0.1.0">
  <dte:SAT ClaseDocumento="dte">
    <dte:DTE ID="DatosCertificados">
      <dte:DatosEmision ID="DatosEmision">';

        //Datos Generales
        $xml .= "<dte:DatosGenerales Tipo=\"" . $this->tipo_documento . "\" " . ($this->es_exportacion ? "Exp=\"SI\"" : "") . " "
                . "FechaHoraEmision=\"" . $this->fechaHoraEmision . "\" CodigoMoneda=\"" . $this->codigoMoneda . "\""
                . //" NumeroAcceso=\"\" ".
                ($this->tipoPersoneria != null ? " TipoPersoneria=\"" . $this->tipoPersoneria . "\" " : "") . "></dte:DatosGenerales>";

        //Datos Emisor
        $xml .= "<dte:Emisor NITEmisor=\"" . $this->nit_emisor . "\" "
                . "NombreEmisor=\"" . $this->nombre_emisor . "\" "
                . "CodigoEstablecimiento=\"" . $this->codigo_establecimiento . "\" "
                . "NombreComercial=\"" . $this->nombre_comercial . "\" "
                . "CorreoEmisor=\"" . $this->correo_emisor . "\" "
                . "AfiliacionIVA=\"" . $this->afiliacion_iva_emisor . "\">";
        //Direccion Emisor
        $xml .= "<dte:DireccionEmisor>
                    <dte:Direccion>" . $this->direccion_emisor . "</dte:Direccion>
                    <dte:CodigoPostal>" . $this->codigo_postal_emisor . "</dte:CodigoPostal>
                    <dte:Municipio>" . $this->municipio_emisor . "</dte:Municipio>
                    <dte:Departamento>" . $this->departamento_emisor . "</dte:Departamento>
                    <dte:Pais>" . $this->pais_emisor . "</dte:Pais>
                </dte:DireccionEmisor>";
        $xml .= "</dte:Emisor>";

        //                . "TipoEspecial=\"" . ($this->es_cui ? "CUI" : "EXT") . "\" "
        //Datos Receptor
        $xml .= "<dte:Receptor "
                . "IDReceptor=\"" . $this->idReceptor . "\" "
                . "NombreReceptor=\"" . $this->nombre_receptor . "\" "
                . "CorreoReceptor=\"" . $this->correo_receptor . "\">";
        //Direccion Emisor
        $xml .= "<dte:DireccionReceptor>
                    <dte:Direccion>" . $this->direccion_receptor . "</dte:Direccion>
                    <dte:CodigoPostal>" . $this->codigo_postal_receptor . "</dte:CodigoPostal>
                    <dte:Municipio>" . $this->municipio_receptor . "</dte:Municipio>
                    <dte:Departamento>" . $this->departamento_receptor . "</dte:Departamento>
                    <dte:Pais>" . $this->pais_receptor . "</dte:Pais>
                </dte:DireccionReceptor>";
        $xml .= "</dte:Receptor>";

        $xml .= '<dte:Frases>
                    <dte:Frase CodigoEscenario="1" TipoFrase="1"></dte:Frase>
                </dte:Frases>';

        //ITEMS
        $xml .= "<dte:Items>";
        foreach ($this->items as $item) {
            $xml .= "<dte:Item NumeroLinea=\"" . $item->numero_linea . "\" "
                    . "BienOServicio=\"" . ($item->es_servicio ? "S" : "B") . "\">
                        <dte:Cantidad>" . $item->cantidad . "</dte:Cantidad>
                        <dte:UnidadMedida>UND</dte:UnidadMedida>
                        <dte:Descripcion>" . $item->descripcion . "</dte:Descripcion>
                        <dte:PrecioUnitario>" . $item->precio_unitario . "</dte:PrecioUnitario>
                        <dte:Precio>" . $item->precio . "</dte:Precio>";
            if ($item->descuento > 0) {
                $xml .= "<dte:Descuento>" . $item->descuento . "</dte:Descuento>";
            } else {
                $xml .= "<dte:Descuento>0.0</dte:Descuento>";
            }
            if (count($item->impuestos) > 0) {
                $xml .= "<dte:Impuestos >";
                foreach ($item->impuestos as $impuesto) {
                    $xml .= "<dte:Impuesto>";
                    $xml .= "<dte:NombreCorto>" . $impuesto["nombre_corto"] . "</dte:NombreCorto>";
                    $xml .= "<dte:CodigoUnidadGravable>" . $impuesto["codigo_unidad_gravable"] . "</dte:CodigoUnidadGravable>";
                    if ($impuesto["monto_gravable"] > 0) {
                        $xml .= "<dte:MontoGravable>" . $impuesto["monto_gravable"] . "</dte:MontoGravable>";
                    }
                    if ($impuesto["unidades_gravables"] > 0) {
                        $xml .= "<dte:CantidadUnidadesGravables>" . $impuesto["unidades_gravables"] . "</dte:CantidadUnidadesGravables>";
                    }

                    $xml .= "<dte:MontoImpuesto>" . $impuesto["monto_impuesto"] . "</dte:MontoImpuesto>";
                    $xml .= "</dte:Impuesto>";
                }
                $xml .= "</dte:Impuestos>";
            }

            $xml .= "<dte:Total>" . $item->total . "</dte:Total>"
                    . "</dte:Item>";
        }
        $xml .= "</dte:Items>";
        $xml .= "<dte:Totales>";
        if (count($this->taxes) > 0) {
            $xml .= "<dte:TotalImpuestos>";
            foreach ($this->taxes as $tax) {
                $xml .= "<dte:TotalImpuesto NombreCorto=\"" . $tax["nombre_corto"] . "\" TotalMontoImpuesto=\"" . $tax["monto_total"] . "\"></dte:TotalImpuesto>";
            }
            $xml .= "</dte:TotalImpuestos>";
        }
        $xml .= "<dte:GranTotal>" . $this->granTotal . "</dte:GranTotal>
                </dte:Totales>";
        $xml .= "
            </dte:DatosEmision>
            </dte:DTE>
            </dte:SAT></dte:GTDocumento>";
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = FALSE;
        $dom->loadXML($xml);
        $dom->formatOutput = TRUE;
        $doc=$dom->saveXML();
        $doc= str_replace("</dte:GTDocumento>", "", $doc);
        $this->xml = $doc;
        $this->base64xml = base64_encode($this->xml);
    }

    private function validarCertificacion() {
        if (!$this->datosGeneralesEstablecidos) {
            $this->logError("Los datos generales no han sido establecidos");
            return false;
        }
        if (!$this->datosEmisorEstablecidos) {
            $this->logError("Los datos del emisor no han sido establecidos");
            return false;
        }
        if (!$this->datosReceptorEstablecidos) {
            $this->logError("Los datos del receptor no han sido establecidos");
            return false;
        }
        if (!$this->direccionEmisorEstablecida) {
            $this->logError("La direccion del emisor no ha sido establecida");
            return false;
        }
        if (!$this->direccionReceptorEstablecida) {
            $this->logError("La direccion del receptor no ha sido establecida");
            return false;
        }
        if (count($this->items) == 0) {
            $this->logError("No han sido agregado items al documento");
            return false;
        } else {
            foreach ($this->items as $item) {
                if (count($item->impuestos) > 0) {
                    
                }
            }
        }
        if ($this->granTotal == 0) {
            $this->logError("El gran total del documento no ha sido asignado");
            return false;
        }


        return true;
    }

    function validarLineasDeImpuestos() {
        $taxesLineas = [];
        $taxesDoc = [];
        foreach (self::$tipo_impuesto as $tipo) {
            $taxesLineas[] = [$tipo => 0];
            $taxesDoc[] = [$tipo => 0];
        }

        foreach ($this->items as $item) {
            foreach ($item->impuestos as $tax) {
                $taxesLineas[$tax["nombre_corto"]] += $tax["monto_impuesto"];
            }
        }
    }

    /*     * ************************
     * UTILS
     * ************************ */

    private function isNITValid($nit) {
        if (strlen($nit) < 1 || strlen($nit) > 13) {
            return false;
        } else {
            if (preg_match(self::NIT_REGEX, $nit) > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    private function isEMAILValid($email) {
        if (preg_match(self::EMAIL_REGEX, $email) > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function doLog($message, $level) {
        if ($this->isInArray($level, self::$log_level)) {
            $this->log[] = ["level" => $level, "message" => $message, "file" => __FILE__];
        } else {
            doLog("El mensaje '" . $mensaje . "' no poseia un nivel de logueo conocido ($level)", self::LOG_LEVEL_WARNING);
        }
        if ($level === self::LOG_LEVEL_ERROR) {
            if ($this->exceptions) {
                throw new Exception($message);
            }
        }
    }

    private function isInArray($search, $array) {
        foreach ($array as $value) {
            if ($search === $value) {
                return true;
            }
        }
        return false;
    }

    private function logError($message) {
        $this->doLog($message, self::LOG_LEVEL_ERROR);
    }

    private function logDebug($message) {
        $this->doLog($message, self::LOG_LEVEL_DEBUG);
    }

    public function getLog() {
        return $this->log;
    }

    function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
    /**
     * Getters y Setters
     */
    
    function getUsuario(): String {
        return $this->usuario;
    }

    function getLlave(): String {
        return $this->llave;
    }

    function getIdentificador(): String {
        return $this->identificador;
    }

    static function getLog_level(): Array {
        return self::$log_level;
    }

    static function getTipos_dte() {
        return self::$tipos_dte;
    }

    static function getTipo_impuesto() {
        return self::$tipo_impuesto;
    }

    static function getMonedas(): array {
        return self::$monedas;
    }

    static function getPaises() {
        return self::$paises;
    }

    static function getTipo_afiliacion_iva(): array {
        return self::$tipo_afiliacion_iva;
    }

    function getCredencialesEstablecidos() {
        return $this->credencialesEstablecidos;
    }

    function getTipo_documento() {
        return $this->tipo_documento;
    }

    function getFechaHoraEmision() {
        return $this->fechaHoraEmision;
    }

    function getEs_exportacion(): bool {
        return $this->es_exportacion;
    }

    function getCodigoMoneda() {
        return $this->codigoMoneda;
    }

    function getTipoPersoneria() {
        return $this->tipoPersoneria;
    }

    function getDatosGeneralesEstablecidos() {
        return $this->datosGeneralesEstablecidos;
    }

    function getNit_emisor() {
        return $this->nit_emisor;
    }

    function getNombre_emisor() {
        return $this->nombre_emisor;
    }

    function getCodigo_establecimiento() {
        return $this->codigo_establecimiento;
    }

    function getNombre_comercial() {
        return $this->nombre_comercial;
    }

    function getCorreo_emisor() {
        return $this->correo_emisor;
    }

    function getAfiliacion_iva_emisor() {
        return $this->afiliacion_iva_emisor;
    }

    function getDatosEmisorEstablecidos() {
        return $this->datosEmisorEstablecidos;
    }

    function getDireccion_emisor() {
        return $this->direccion_emisor;
    }

    function getCodigo_postal_emisor() {
        return $this->codigo_postal_emisor;
    }

    function getMunicipio_emisor() {
        return $this->municipio_emisor;
    }

    function getDepartamento_emisor() {
        return $this->departamento_emisor;
    }

    function getPais_emisor() {
        return $this->pais_emisor;
    }

    function getDireccionEmisorEstablecida() {
        return $this->direccionEmisorEstablecida;
    }

    function getIdReceptor() {
        return $this->idReceptor;
    }

    function getNombre_receptor() {
        return $this->nombre_receptor;
    }

    function getCorreo_receptor() {
        return $this->correo_receptor;
    }

    function getEs_cui() {
        return $this->es_cui;
    }

    function getDatosReceptorEstablecidos() {
        return $this->datosReceptorEstablecidos;
    }

    function getDireccion_receptor() {
        return $this->direccion_receptor;
    }

    function getCodigo_postal_receptor() {
        return $this->codigo_postal_receptor;
    }

    function getMunicipio_receptor() {
        return $this->municipio_receptor;
    }

    function getDepartamento_receptor() {
        return $this->departamento_receptor;
    }

    function getPais_receptor() {
        return $this->pais_receptor;
    }

    function getDireccionReceptorEstablecida() {
        return $this->direccionReceptorEstablecida;
    }

    function getItems() {
        return $this->items;
    }

    function getTaxes() {
        return $this->taxes;
    }

    function getXml() {
        return $this->xml;
    }

    function getXmlFirmado() {
        return $this->xmlFirmado;
    }

    function getXmlCertificado() {
        return $this->xmlCertificado;
    }

    function getXmlFirmadoBase64() {
        return $this->xmlFirmadoBase64;
    }

    function getBody() {
        return $this->body;
    }

    function getBodyFirma() {
        return $this->bodyFirma;
    }

    function getJsonFirma() {
        return $this->jsonFirma;
    }

    function getJsonCertificacion() {
        return $this->jsonCertificacion;
    }

    function getSerie() {
        return $this->serie;
    }

    function getNumero() {
        return $this->numero;
    }

    function getUuid() {
        return $this->uuid;
    }

    function setUsuario(String $usuario): void {
        $this->usuario = $usuario;
    }

    function setLlave(String $llave): void {
        $this->llave = $llave;
    }

    function setIdentificador(String $identificador): void {
        $this->identificador = $identificador;
    }

    static function setLog_level(Array $log_level): void {
        self::$log_level = $log_level;
    }

    static function setTipos_dte($tipos_dte): void {
        self::$tipos_dte = $tipos_dte;
    }

    static function setTipo_impuesto($tipo_impuesto): void {
        self::$tipo_impuesto = $tipo_impuesto;
    }

    static function setMonedas(array $monedas): void {
        self::$monedas = $monedas;
    }

    static function setPaises($paises): void {
        self::$paises = $paises;
    }

    static function setTipo_afiliacion_iva(array $tipo_afiliacion_iva): void {
        self::$tipo_afiliacion_iva = $tipo_afiliacion_iva;
    }

    function setCredencialesEstablecidos($credencialesEstablecidos): void {
        $this->credencialesEstablecidos = $credencialesEstablecidos;
    }

    function setTipo_documento($tipo_documento): void {
        $this->tipo_documento = $tipo_documento;
    }

    function setFechaHoraEmision($fechaHoraEmision): void {
        $this->fechaHoraEmision = $fechaHoraEmision;
    }

    function setEs_exportacion(bool $es_exportacion): void {
        $this->es_exportacion = $es_exportacion;
    }

    function setCodigoMoneda($codigoMoneda): void {
        $this->codigoMoneda = $codigoMoneda;
    }

    function setTipoPersoneria($tipoPersoneria): void {
        $this->tipoPersoneria = $tipoPersoneria;
    }

    function setDatosGeneralesEstablecidos($datosGeneralesEstablecidos): void {
        $this->datosGeneralesEstablecidos = $datosGeneralesEstablecidos;
    }

    function setNit_emisor($nit_emisor): void {
        $this->nit_emisor = $nit_emisor;
    }

    function setNombre_emisor($nombre_emisor): void {
        $this->nombre_emisor = $nombre_emisor;
    }

    function setCodigo_establecimiento($codigo_establecimiento): void {
        $this->codigo_establecimiento = $codigo_establecimiento;
    }

    function setNombre_comercial($nombre_comercial): void {
        $this->nombre_comercial = $nombre_comercial;
    }

    function setCorreo_emisor($correo_emisor): void {
        $this->correo_emisor = $correo_emisor;
    }

    function setAfiliacion_iva_emisor($afiliacion_iva_emisor): void {
        $this->afiliacion_iva_emisor = $afiliacion_iva_emisor;
    }

    function setDatosEmisorEstablecidos($datosEmisorEstablecidos): void {
        $this->datosEmisorEstablecidos = $datosEmisorEstablecidos;
    }

    function setDireccion_emisor($direccion_emisor): void {
        $this->direccion_emisor = $direccion_emisor;
    }

    function setCodigo_postal_emisor($codigo_postal_emisor): void {
        $this->codigo_postal_emisor = $codigo_postal_emisor;
    }

    function setMunicipio_emisor($municipio_emisor): void {
        $this->municipio_emisor = $municipio_emisor;
    }

    function setDepartamento_emisor($departamento_emisor): void {
        $this->departamento_emisor = $departamento_emisor;
    }

    function setPais_emisor($pais_emisor): void {
        $this->pais_emisor = $pais_emisor;
    }

    function setDireccionEmisorEstablecida($direccionEmisorEstablecida): void {
        $this->direccionEmisorEstablecida = $direccionEmisorEstablecida;
    }

    function setIdReceptor($idReceptor): void {
        $this->idReceptor = $idReceptor;
    }

    function setNombre_receptor($nombre_receptor): void {
        $this->nombre_receptor = $nombre_receptor;
    }

    function setCorreo_receptor($correo_receptor): void {
        $this->correo_receptor = $correo_receptor;
    }

    function setEs_cui($es_cui): void {
        $this->es_cui = $es_cui;
    }

    function setDatosReceptorEstablecidos($datosReceptorEstablecidos): void {
        $this->datosReceptorEstablecidos = $datosReceptorEstablecidos;
    }

    function setDireccion_receptor($direccion_receptor): void {
        $this->direccion_receptor = $direccion_receptor;
    }

    function setCodigo_postal_receptor($codigo_postal_receptor): void {
        $this->codigo_postal_receptor = $codigo_postal_receptor;
    }

    function setMunicipio_receptor($municipio_receptor): void {
        $this->municipio_receptor = $municipio_receptor;
    }

    function setDepartamento_receptor($departamento_receptor): void {
        $this->departamento_receptor = $departamento_receptor;
    }

    function setPais_receptor($pais_receptor): void {
        $this->pais_receptor = $pais_receptor;
    }

    function setDireccionReceptorEstablecida($direccionReceptorEstablecida): void {
        $this->direccionReceptorEstablecida = $direccionReceptorEstablecida;
    }

    function setItems($items): void {
        $this->items = $items;
    }

    function setTaxes($taxes): void {
        $this->taxes = $taxes;
    }

    function setXml($xml): void {
        $this->xml = $xml;
    }

    function setXmlFirmado($xmlFirmado): void {
        $this->xmlFirmado = $xmlFirmado;
    }

    function setXmlCertificado($xmlCertificado): void {
        $this->xmlCertificado = $xmlCertificado;
    }

    function setXmlFirmadoBase64($xmlFirmadoBase64): void {
        $this->xmlFirmadoBase64 = $xmlFirmadoBase64;
    }

    function setBody($body): void {
        $this->body = $body;
    }

    function setBodyFirma($bodyFirma): void {
        $this->bodyFirma = $bodyFirma;
    }

    function setJsonFirma($jsonFirma): void {
        $this->jsonFirma = $jsonFirma;
    }

    function setJsonCertificacion($jsonCertificacion): void {
        $this->jsonCertificacion = $jsonCertificacion;
    }

    function setSerie($serie): void {
        $this->serie = $serie;
    }

    function setNumero($numero): void {
        $this->numero = $numero;
    }

    function setUuid($uuid): void {
        $this->uuid = $uuid;
    }
}

class Item {

    /**
     * Es necesario que esta variable sea definida segun el orden del sistema pues en las anulaciones se necesita que se envie el mismo correlativo
     */
    public $numero_linea;
    public $cantidad;
    public $descripcion;
    public $precio_unitario;
    public $precio;
    public $total;
    public $es_servicio = false;
    public $impuestos = [];
    public $descuento;

    /**
     * Constructor de la clase ITEM
     * @param int $numero_linea Correlativo del ítem dentro del DTE. 
     * En el caso de Notas de Débito y Notas de Crédito identifica el renglón 
     * o ítem del documento original.
     * @param float $cantidad Indica la cantidad de unidades del ítem.
     * @param string $descripcion Indica la descripción del ítem.
     * @param float $precio_unitario Precio de cada unidad del ítem en la moneda en que se emite el DTE (quetzales, dólares, euros, etc.). CON IMPUESTOS
     * @param float $precio PrecioUnitario multiplicado por Cantidad
     * @param float $total Variable precio menos descuento (ver addDescuento) CON IMPUESTOS
     * 
     */
    function __construct($numero_linea, $cantidad, $descripcion, $precio_unitario, $precio, $total, $es_servicio = false) {
        $this->numero_linea = $numero_linea;
        $this->cantidad = $cantidad;
        $this->descripcion = $descripcion;
        $this->precio_unitario = $precio_unitario;
        $this->precio = $precio;
        $this->total = $total;
        $this->es_servicio = $es_servicio;
    }

    /**
     * Funcion que añade el descuento global al item. (Solo debe ser llamado una vez)
     * @param float $descuento Indica el descuento a aplicar sobre el elemento precio.
     */
    function addDescuento($descuento) {
        $this->descuento = $descuento;
    }

    /**
     * Funcion que añade una linea de impuesto aplicable al item, 
     * debe ser llamado por cada impuesto aplicable a la linea maximo 20 impuestos por linea.
     * @param string $nombre_corto Nombre con el que se conoce el impuesto, ver
     *  ARRAY FEL::tipo_impuesto 
     * @param integer $codigoUnidadGravable Codigo de Unidad Gravable en el catalogo para el IVA (1 = IVA 12, 2 = IVA 0 para exportaciones)
     * @param float $montoImpuesto Monto del impuesto para el item
     * @param float $montoGravable monto gravable de la linea
     * @param integer $unidadesGravables Cantidad de unidades sobre las 
     * que se aplica el impuesto
     * 
     */
    function addImpuesto($nombre_corto, $codigoUnidadGravable, $montoImpuesto, $montoGravable, $unidadesGravables = 0) {
        $impuesto = [];
        $impuesto["nombre_corto"] = $nombre_corto;
        $impuesto["codigo_unidad_gravable"] = $codigoUnidadGravable;
        $impuesto["monto_impuesto"] = $montoImpuesto;
        $impuesto["monto_gravable"] = $montoGravable;
        $impuesto["unidades_gravables"] = $unidadesGravables;
        $this->impuestos[] = $impuesto;
    }

}
