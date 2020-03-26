<?php
/**
*    Clase NokTemplate
*
*    @author Nok http://www.jpw.com.ar - nok@jpw.com.ar
*
*    @copyright Juan Pablo Winiarczyk
*
*    @version 1.2.0 Beta
*
*   Versión 1.2.0 cambios desde 1.1.6
*   - Mejoras ahora a los metodos asignar, cargar, cargarVirtual y asignarDinamico, se le puede pasar un array() con los pares clave => valor
*	- Agregado método setDirectorio(), para cambiar el directorio donde se ubican los tpls en tiempo de ejecución.
*   - En el método expandir() el 3er parametro, que puede ser Booleano o un array(). Si es un array() se le pasan las variables que no desean ser evaluadas.
*     Si es Booleano funciona como en las versiones anteriores.
*
*   Versión 1.1.6 cambios desde 1.1.4
*   - Bug Fix: Se reparo un bug existente en el uso de las variables dinámicas
*     mediante el método asignarDinamico(); imposibilitando el uso de más de una variable.
*     Descubierto por Zar Donkan http://www.jpw.com.ar/index.php?lugar=foro&accion=leer&id=15
*
*   Versión 1.1.4 cambios desde 1.1.2
*   - Se agrega la posibilidad de poner el signo menos al expandir un template, esto resetea el contenido de la varible destino.
*     Propuesto por Cluster en http://www.forosdelweb.com
*
*   Versión 1.1.2 cambios desde 1.1.0
*   - Se agrega el método cargarVirtual que toma el contenido de una variable (String)
*     Y la maneja como un si fuera un template, sin tener que obtenerlo desde un archivo.
*     Util cuando los templates estan almacenados en una base de datos y no son archivos reales.
*   - Se reparo un smallBug en la utilización de bloques anidados, reportado por
*     Ababol en http://www.forosdelweb.com foro php.
*
*   Versión 1.1.0 cambios desde 1.0.0
*   - Ahora soporta bloques anidados, es decir, los bloques se pueden definir uno dentro del otro. Propuesto por Pablo (Webstudio).
*     - Se optimizo la definicion de bloques. Se reemplazaron las Expresiones POSIX por las PERL (Era lo unico que funcionaba con estas funciones)
*       Logrando mayor eficiencia y velocidad.
*   - Se le agrego una bandera al método expandir($variable, $template, $analizar = True), con la cual se puede omitir el analisis del template, en caso de que no sea necesario.
*
*    Clase para manejar templates. Incluye sistema de Cache en archivo externo.
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

class NokTemplate {
    /** Guarda la version actual de la clase
    * @access private
    **/
    var $_version = '1.2.0 Beta';

    /** Directorio donde se encuentran los archivos Templates.
    * @access private
    **/
    var $_path = '';

    /** Variable que guarda el contiene de los archivos Templates.
    * @access private
    **/
    var $_archivosArray = array();

    /** Variable que contiene la definición de variables Templates.
    * @access private
    **/
    var $_variablesArray = array();

    /** Variable que guarda el modo de operacion de la cache. 0: No usar, 1: No existe archivo, 2: Existe archivo
    * @access private
    **/
    var $_usarCache = 0;

    /**  Variable que guarda la ubicacion y el nombre del archivo usado como cache.
    * @access private
    **/
    var $_cache = '';

    /**  Variable que guarda la ubicacion y el nombre del archivo usado como cache.
    * @access private
    **/
    var $_debug = False;

    /** Variable utilizada como bandera. Especifica si hay o no definidas variables dinamicas.
    * @access private
    **/
    var $_variableDinamica = False;

    /** Variable que contiene la especificacion de Variables dentro de un Template.
    * @access private
    **/
    var $_tagsArray = array('abrir' => '{', 'cerrar' => '}');

    /** Variable que contiene la especificacion de Bloques dentro de un Template.
    * @access private
    **/
    var $_tagsBloqueArray = array('abrir' => '<!-- inicioBloque:', 'cerrar' => '<!-- finBloque:', 'close' => '-->');

    /** Variable que contiene la especificacion de Variables dentro de la cache.
    * @access private
    **/
    var $_tagsVarDinamicaArray = array('abrir' => '<!-- inicioVarDinamica:', 'cerrar' => '<!-- finVarDinamica:', 'close' => '-->');


    /** Metodo Constructor de la Clase
    * @param string $directorio especifica el path hacia el directorio donde se encuentran los templates.
    * @access private
    **/
    function NokTemplate($directorio='.') {
    //
        $this->setDirectorio($directorio);
    }

    /** Carga un archivo template y le asigna una clave identificatoria.
    * @access public
    * @param string $clave Indica el nombre con el cual se referenciará al template.
    * @param string $nombreArchivo Indica el path relativo o absoluto del archivo template
    **/
    function cargar() {
    //
		if($this->_usarCache != 2) {
        	$parametros = array();
        	switch (func_num_args()) {
        		case 1:
        	    	if(is_array(func_get_arg(0))) {
        	    		$parametros = func_get_arg(0);
					}
					else {
						error('Parametro erroneo');
					}
        		break;
        		case 2:
        	    	$parametros[func_get_arg(0)] = func_get_arg(1);
        		break;
          	default:
	        	    $this->error('Numero de parametros incorrecto');
    	    	break;
        	}

			foreach ($parametros as $clave => $nombreArchivo) {
    			$this->_archivosArray[$clave] = $this->obtenerArchivo($nombreArchivo);
        	}
		}
    }

    /** Carga un template y le asigna una clave identificatoria. El template en este caso
    *   Es el contenido de un string directamente, no un archivo. Util cuando los templates
    *   Se encuentra en una Base de datos.
    * @access public
    * @param string $clave Indica el nombre con el cual se referenciará al template.
    * @param string $contenido Es la variable string que contiene el template.
    **/
    function cargarVirtual() {
    //
        if($this->_usarCache != 2) {
			$parametros = array();
        	switch (func_num_args()) {
        		case 1:
        	    	if(is_array(func_get_arg(0))) {
        	    		$parametros = func_get_arg(0);
					}
					else {
						error('Parametro erroneo');
					}
        		break;
        		case 2:
        	    	$parametros[func_get_arg(0)] = func_get_arg(1);
        		break;
        		default:
	        	    $this->error('Numero de parametros incorrecto');
    	    	break;
	        }
             $this->_archivosArray = array_merge($this->_archivosArray, $parametros);
        }
    }

    /** Metodo que asigna un valor a una variable.
    * NokTemplate::asignar()
    * @param mixed $variable Especifica el nombre de la variable a ser asignada. O si es array() especifica
    *               pares $variable=>$valor
    * @param string $valor Especifica el valor asignado a la variable
    * @access public
    */
    function asignar() {
    //
    	$parametros = array();
        switch (func_num_args()) {
        	case 1:
        		if(is_array(func_get_arg(0))) {
        	    	$parametros = func_get_arg(0);
				}
			else {
				error('Parametro erroneo');
			}
        	break;
        	case 2:
        		$parametros[func_get_arg(0)] = func_get_arg(1);
        	break;
        	default:
	        	$this->error('Numero de parametros incorrecto');
    	    break;
        }
		$this->_variablesArray = array_merge($this->_variablesArray, $parametros);
    }

    /** Metodo que expande (parse) una variable con el contenido de un Template.
    * NokTemplate::expandir()
    * @param string $variable Especifica el nombre de la variable en la cual se almacenará el contenido del template
    * @param string $clave especifica el template que sera expandido
    * @param boolean $analizar Especifica si se analizara en busqueda de variables o no el template. O en caso de especificar un array, excluye las vars del analisis.
    * @access public
    */
    function expandir() {
    //
        switch (func_num_args()) {
        	case 2:
				$variable = func_get_arg(0);
				$clave = func_get_arg(1);
				$mixed = True;
        	break;
        	case 3:
        	    $variable = func_get_arg(0);
				$clave = func_get_arg(1);
				$mixed = func_get_arg(2);
        	break;
        	default:
	            $this->error('Numero de parametros incorrecto');
    	    break;
        }
    
        if (!empty($variable) AND !empty($clave)) {

            if($clave{0} == '-'){
                $this->_variablesArray[$variable]='';
            }
            else {
                if($this->_usarCache != 2){
                    $nuevoValor = '';
                    $concatenar = False;

                    if($clave{0} == '+' OR $clave{0} == '.'){
                        $clave = substr($clave, 1);
                        $concatenar = True;
                    }
                    //Excluyo las variables
                    if (is_array($mixed)) {
						$temp = $this->_archivosArray[$clave];
						foreach($mixed as $var) {
       	    				$this->_archivosArray[$clave] = str_replace($this->_tagsArray['abrir'] . $var . $this->_tagsArray['cerrar'],
					    	'%%%' . $var . '%%%',
							$this->_archivosArray[$clave]);
       	    			}
					}
					else {
						$analizar = $mixed;
					}

                    if($analizar) {
                        // Las lineas mas importantes, donde se realiza la interpolación de variables.
                        // Interpolación de variables -->
                        if (!$this->_debug) {
                           $nuevoValor = preg_replace('/{([a-zA-Z0-9_]+)}/e', "\$this->_variablesArray['\\1']", $this->_archivosArray[$clave]);
                        }
                        else {
                           $nuevoValor = preg_replace('/{([a-zA-Z0-9_]+)}/e', "\$this->debugger(\\1)", $this->_archivosArray[$clave]);
                        }
                         // <-- Interpolación de variables
                    }
                    else {
                        $nuevoValor = $this->_archivosArray[$clave];
                    }

					// Vuelvo las variables excluidas a la normalidad
					if (is_array($mixed)) {
       	    			foreach($mixed as $var) {
       	    				$nuevoValor = str_replace('%%%' . $var . '%%%',
       	    				$this->_tagsArray['abrir'] . $var . $this->_tagsArray['cerrar'],
							$nuevoValor);
						}
						//Le devuelvo el valor original
						$this->_archivosArray[$clave] = $temp;
					}

 					if ($concatenar) {
                    	$this->_variablesArray[$variable] .= $nuevoValor;
                    }
                    else {
                        $this->_variablesArray[$variable] = $nuevoValor;
                    }
                }
            }
        }
        else {
            $this->error("Identificador no válido.");
        }
    }

    /** Imprime la Salida del Template
    * NokTemplate::imprimir()
    * @param string $variable nombre de la variable que contiene la pagina lista para ser mostrada
    * @access public
    */
    function imprimir($variable) {
        print $this->exportar($variable);
    }

    /* Devuelve el valor de un variable.
    * NokTemplate::exportar()
    * @access public
    */
    function exportar($variable) {
    //
        if (!empty($variable)) {
            $idArchivo = 0;
            switch ($this->_usarCache) {
                case 0:
                    return $this->_variablesArray[$variable];
                break;
                case 1: // No existe el archivo cache. Debo guardarlo.
                    $idArchivo = fopen($this->_cache, 'w');
                    fwrite($idArchivo, $this->_variablesArray[$variable]);
                    fclose($idArchivo);

                    return $this->_variablesArray[$variable];
                break;
                default: // Existe el archivo cache. Primero busco variables dinamicas y despues lo muestro.
                    $template = implode('', file($this->_cache));

                    if ($this->_variableDinamica) {
                        $template = preg_replace('/' .
												 $this->_tagsVarDinamicaArray['abrir'] .
												 ' ([a-zA-Z0-9_]+) ' .
												 $this->_tagsVarDinamicaArray['close'] .
												 '.*' .
												 $this->_tagsVarDinamicaArray['cerrar'] .
												 ' (\\1) ' .
												 $this->_tagsVarDinamicaArray['close'] .
												 '/es', "\$this->_variablesArray['\\1']", $template);
                    }
                    return $template;
                break;
            }
        }
        else {
            $this->error("Identificador no válido.");
        }
    }

    /** Metodo que determina el identificador de la cache.
    * @access public
    **/
    function setCache($idCache, $cacheExpira = 0){
    //
        if (!empty($idCache)) {
            $this->_cache = $this->_path . $idCache . '.cache';
            $this->_usarCache = 1;

            if (($cacheExpira == 0 OR (time() - @filemtime($this->_cache)) < $cacheExpira) AND file_exists($this->_cache)) {
                    $this->_usarCache = 2;
            }
        }
        else {
            $this->error('Identificador de cache nulo. Debe especificar alguno.');
        }
    }

    /** Metodo que elimina la cache existente.
    * @access public
    **/
    function limpiarCache($idCache){
    //
        @unlink ($this->_path . $idCache);
    }

    /** Metodo que asigna un valor a una variable guardada en la cache.
    * NokTemplate::asignarDinamico()
    * @param string $variable Especifica el nombre de la variable a ser asignada
    * @param string $valor Especifica el valor asignado a la variable
    * @access public
    */
    function asignarDinamico($variable, $valor='') {
    //
        if($this->_usarCache > 0) {
			$parametros = array();
        	switch (func_num_args()) {
        		case 1:
        			if(is_array(func_get_arg(0))) {
        	    		$parametros = func_get_arg(0);
					}
				else {
					error('Parametro erroneo');
				}
        		break;
        		case 2:
        			$parametros[func_get_arg(0)] = func_get_arg(1);
        		break;
        		default:
		        	$this->error('Numero de parametros incorrecto');
    		    break;
    	    }
            $this->_variableDinamica = True;

            foreach($parametros as $var => $valor) {
            	$this->_variablesArray[$var] = $this->_tagsVarDinamicaArray['abrir'] ." ". $var ." ". $this->_tagsVarDinamicaArray['close'] . $valor . $this->_tagsVarDinamicaArray['cerrar'] ." ". $var ." ". $this->_tagsVarDinamicaArray['close'];
        	}
		}
        else {
            $this->error('Debe activar la cache para utilizar esta función.');
        }
    }

    /** Metodo que define un "Template" dentro de otro template.
    * @param string $bloque Indica la clave identificatoria el path relativo o absoluto del archivo template
    * @param string $nombreArchivo Indica el path relativo o absoluto del archivo template
    * @access public
    **/
    function definirBloque($bloque, $template) {
    //
        if ($this->_usarCache != 2) {
            $stringBloque = array();
            if(!empty($bloque) AND !empty($template)) {

                if(preg_match('/' . $this->_tagsBloqueArray['abrir'] . '[ ]*' . $bloque . '[ ]*' . $this->_tagsBloqueArray['close'] .
                              '(.*)' .
                              $this->_tagsBloqueArray['cerrar'] . '[ ]*' . $bloque . '[ ]*' . $this->_tagsBloqueArray['close'] . '/s',
                              $this->_archivosArray[$template], $stringBloque)){

                        //Quito sub bloques si existieran.
                        $this->_archivosArray[$bloque] = preg_replace('/' . $this->_tagsBloqueArray['abrir'] . ' (.*) ' . $this->_tagsBloqueArray['close'] . '(.*)' . $this->_tagsBloqueArray['cerrar'] . ' \\1 ' . $this->_tagsBloqueArray['close'] . '/s', '', $stringBloque[1]);
                }
                else {
                    $this->error("Bloque \"$bloque\" inexistente dentro del Template.");
                }
            }
        }
    }

    /* Habilita o deshabilita el modo Debug.
    * NokTemplate::debug()
    * @access public
    */
    function debug($bool = True){
    //
        $this->_debug = $bool;
    }

    /* Devuelve la versión actual de la clase.
    * NokTemplate::version()
    * @access public
    */
    function version(){
    //
        return $this->_version;
    }

    /* Imprime el error ocurrido si lo hubiese y detiene la ejecucion.
    * NokTemplate::error()
    * @access public
    * @param string mensaje Mensaje de error.
    */
    function error($mensaje = '') {
    //
        if (!empty($mensaje)) {
            exit('(NokTemplate) <b>Error: </b>' . $mensaje);
        }
    }

    /** Metodo que chequea la existencia del directorio donde se albergan los templates
    * @param string $directorio especifica un directorio.
    * @access private
    **/
    function checkearPath($directorio){
    //
        if ($directorio{strlen($directorio)-1} == '/') {
            $directorio = substr($directorio, 0, strlen($directorio)-1);
        }

        if (@is_dir($directorio)) {
            return ($directorio . '/');
        }
        else {
            $this->error("El directorio \"$directorio\" no existe.");
        }
    }

    /** Metodo para acceder al archivo Template. Realiza chequeo de existencia
    * @access private
    * @param $nombreArchivo Indica el nombre del archivo Template.
    **/
    function obtenerArchivo($nombreArchivo){
    //
        $pathReal = $this->_path . $nombreArchivo;

        if($nombreArchivo{0} == '*') {
            $pathReal = substr($nombreArchivo, 1);
        }

        if (file_exists($pathReal)) {
            return implode('', file($pathReal));
        }
        else {
            $this->error("Archivo template: \"$nombreArchivo \" no válido.");
        }
    }

    /* Chequea la existencia o no de una variable.
    * NokTemplate::debugger()
    * @access private
    * @param string variable Variable a chequear.
    */
    function debugger($variable) {
    //
        if (!isset($this->_variablesArray[$variable])) {
            $this->error("<b>Debug:</b> Variable \"$variable\" definida en el Template, no está inicializada.");
        }
        else {
            return $this->_variablesArray[$variable];
        }
    }
    
    /* Setea el directorio donde se encuentran los templates por defecto.
    * NokTemplate::setDirectorio()
    * @access private
    * @param string dir path al directorio.
    */
    function setDirectorio($directorio = '.') {
	//
		$this->_path = $this->checkearPath($directorio);
	}
}
?>
