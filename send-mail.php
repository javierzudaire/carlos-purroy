<?php

session_start();


require_once("lib/class.phpmailer.php");

/*#################################################################################################
|	Funcion: ntml2txt
|	Convierte una cadena en html a texto
| 	@access	public   |	@param	string    | 	@return	string
|################################################################################################*/	
function html2txt ( $document )
{
	$search = array("'<script[^>]*?>.*?</script>'si",	// strip out javascript
			"'<[\/\!]*?[^<>]*?>'si",		// strip out html tags
			"'([\r\n])[\s]+'",			// strip out white space
			"'@<![\s\S]*?–[ \t\n\r]*>@'",
			"'&(quot|#34|#034|#x22);'i",		// replace html entities
			"'&(amp|#38|#038|#x26);'i",	        // added hexadecimal values
			"'&(lt|#60|#060|#x3c);'i",
			"'&(gt|#62|#062|#x3e);'i",
			"'&(nbsp|#160|#xa0);'i",
			"'&(iexcl|#161);'i",
			"'&(cent|#162);'i",
			"'&(pound|#163);'i",
			"'&(copy|#169);'i",
			"'&(reg|#174);'i",
			"'&(deg|#176);'i",
			"'&(#39|#039|#x27);'",
			"'&(euro|#8364);'i",			// europe
			"'&a(uml|UML);'",			// german
			"'&o(uml|UML);'",
			"'&u(uml|UML);'",
			"'&A(uml|UML);'",
			"'&O(uml|UML);'",
			"'&U(uml|UML);'",
			"'&szlig;'i",
			);
	$replace = array(	"", 
				"",
				" ",
				"\"",
				"&",
				"<",
				">",
				" ",
				chr(161),
				chr(162),
				chr(163),
				chr(169),
				chr(174),
				chr(176),
				chr(39),
				chr(128),
				"ä",
				"ö",
				"ü",
				"Ä",
				"Ö",
				"Ü",
				"ß",
			);
		$text = preg_replace($search,$replace,$document);
		return trim ( $text );
}

$mail = new PHPMailer(true);

$resu = "no";
$mensag = "50";
$ristra = "";

try {
 	$mail->IsSendmail() ; // set mailer to use SMTP
	$mail->CharSet = "UTF-8";
	$mail->Encoding = "base64";
	$mail->SetFrom("noreply@carlospurroyesculturas.com", "Página Web");
	$mail->Host = "server.webernando.com";     // probar tambien con     server.webernando.com
	$mail->Port = "587";
	$mail->SMTPAuth = true;
 	$mail->SMTPSecure = "ttl";
	$mail->Username = "webmaster@carlospurroyesculturas.com";
	$mail->Password = "Wm3100cp"; 
	$mail->AddAddress("carlos@carlospurroyesculturas.com");  
	$mail->AddReplyTo($_POST['email'], $_POST['nombre']);
	$mail->SMTPDebug  = false;  // enables SMTP debug information (for testing)
	$mail->WordWrap = 80; // ancho del mensaje
	$mail->IsHTML(true); // enviar como HTML

	// Añadimos el mensaje: asunto, cuerpo del mensaje en HTML y en formato

	$mail->Subject  =  "Has recibido un mensaje de " . $_POST['nombre'];
	$cuerpo = "<table style='background-color:eee;font-family:Arial,Tahoma;width:800px;margin:30px 20px;border-collapse:separate;border-spacing:10px'>";
	$cuerpo = $cuerpo . "<tr><td>Nombre</td><td>" . $_POST['nombre'] . "</td></tr>";	
	$cuerpo = $cuerpo . "<tr><td>Email</td><td>" . $_POST['email'] . "</td></tr>";
	$cuerpo = $cuerpo . "<tr><td>Mensaje</td><td>" . nl2br($_POST['mensaje']) . "</td></tr></table>";

	$mail->Body = $cuerpo;

	$mail->AltBody  =  html2txt ( $cuerpo ); // Para los que no pueden recibir en formato HTML
	$mail->Send();

} catch (phpmailerException $e) {
	//Pretty error messages from PHPMailer
	$mensag = "90";

} catch (Exception $e) {
	 //Boring error messages from anything else!
	$mensag = "91";
 
}

// Hasta aqui el envio de mail
$vamos = "Location: https://carlospurroyesculturas.com/contacto.html?status=sent#message";

header( $vamos );
exit();

?>






