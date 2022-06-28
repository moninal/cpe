<?php 
// creado por manuel 25/03/2021

if(!isset($conexion)) {
    require_once(dirname(__DIR__)."/config.php");
    //require_once("../config.php");

}

class ClsModel {

    private $sql;
    private $pdo;
    private $result;

    

    public function __construct() {
        global $conexion;
        $this->pdo = $conexion;
        $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); // para que muestre los errores del pdo
        $this->errorCode = "";
        $this->errorInfo = array();
    }


    public function query($query) {
        try{
            $this->result = $this->pdo->query($query);
            $this->sql = $query;
            # Ajustamos el modo de obtención de datos
            $this->result->setFetchMode(PDO::FETCH_OBJ);
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
            return $this->result;
        }
        catch(PDOException $err) {
            // Mostramos un mensaje genérico de error.
            // print_r($err);
            // echo "Error: ejecutando consulta SQL.";
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
        }

    }

    public function query_array($query) {
        try{
            $this->result = $this->pdo->query($query);
            
            # Ajustamos el modo de obtención de datos
            $this->result->setFetchMode(PDO::FETCH_ASSOC);
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
            return $this->result;
        }
        catch(PDOException $e) {
            // Mostramos un mensaje genérico de error.
            // print_r($e);
            // echo "Error: ejecutando consulta SQL.";
            // echo 'Error: ' . $e->getMessage();
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
        }

    }



    public function insertar($tabla, $datos = array()) {
        try {

            $array = explode(".", $tabla);
            $schema = $array[0];
            $table = $array[1];
            
            $sql = "SELECT cols.column_name, cols.data_type
            FROM information_schema.columns cols
            where cols.table_name= '{$table}' and cols.table_schema='{$schema}'";
            $result = $this->query($sql);
            
            $CadenaInsert = "INSERT INTO {$tabla} (\n  ";
            $campos = array();
            $values = array();
            $valores = array();
            while($row = $result->fetch()) {
                if(in_array(":".$row->column_name, array_keys($datos))) {
                    array_push($campos, $row->column_name."\n");
                    array_push($values, ":".$row->column_name."\n");
                    array_push($valores, $datos[":".$row->column_name]."\n");
                }
            
            }

            $CadenaCampos = implode(", ", $campos);
            $CadenaValues = implode(", ", $values);
            $CadenaValores = implode(", ", $valores);

            $this->sql = $CadenaInsert . $CadenaCampos . ") VALUES (\n  ".$CadenaValores.");";

            $CadenaInsert .= $CadenaCampos . ") VALUES (\n  ".$CadenaValues.");";
            //echo $CadenaInsert;
     
            $this->result = $this->pdo->prepare($CadenaInsert);
            $this->result->execute($datos);
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
            return $this->result;
        } catch(PDOException $e) {
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
            // return $this->result;
            // return $this->result;
            print_r($e);
            // echo 'Error insertar: ' . $e->getMessage();
        }
    }

  

    public function modificar($tabla, $datos = array(), $condicion = array()) {
        try {
            $array = explode(".", $tabla);
            $schema = $array[0];
            $table = $array[1];

            $sql = "SELECT cols.column_name, cols.data_type
            FROM information_schema.columns cols
            where cols.table_name= '{$table}' and cols.table_schema='{$schema}'";
            $result = $this->query($sql);
            
            $CadenaUpdate = "UPDATE {$tabla} SET\n  ";
            $campos = array();
            $where = array();
            $valores = array();
            $condicional = array();

            while($row = $result->fetch()) {
                if(in_array(":".$row->column_name, array_keys($datos))) {
                    array_push($campos, $row->column_name."=:".$row->column_name."\n");
                    array_push($valores,  $row->column_name."=".$datos[":".$row->column_name]."\n");
                }

                if(in_array(":".$row->column_name, array_keys($condicion))) {
                    array_push($where, $row->column_name."=:".$row->column_name);
                    array_push($condicional, $row->column_name."=".$condicion[":".$row->column_name]);
                    
                }
                
                
            }

          
            $CadenaCampos = implode(", ", $campos);
            $CadenaWhere = implode(" AND ", $where);
            $CadenaValores = implode(", ", $valores);
            $CadenaCondional = implode(" AND ", $condicional);

            $this->sql = $CadenaUpdate . $CadenaValores . "WHERE ".$CadenaCondional.";";

            $CadenaUpdate .= $CadenaCampos . "WHERE ".$CadenaWhere.";";
            //  echo $CadenaUpdate;
            
            $data = array_merge($datos, $condicion);

            $this->result = $this->pdo->prepare($CadenaUpdate);
            $this->result->execute($data);
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
            return $this->result;
        } catch(PDOException $e) {
            // print_r($e);
            // echo 'Error: ' . $e->getMessage();
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
        }
    }


    public function eliminar($tabla, $condicion = array()) {
        try {
            $array = explode(".", $tabla);
            $schema = $array[0];
            $table = $array[1];

            $sql = "SELECT cols.column_name, cols.data_type
            FROM information_schema.columns cols
            where cols.table_name= '{$table}' and cols.table_schema='{$schema}'";
            $result = $this->query($sql);
            
            $CadenaDelete = "DELETE FROM {$tabla} ";
           
            $where = array();
            $condicional = array();
            while($row = $result->fetch()) {
                if(in_array(":".$row->column_name, array_keys($condicion))) {
                    array_push($where, $row->column_name."=:".$row->column_name);
                    array_push($condicional, $row->column_name."=".$condicion[":".$row->column_name]);
                }
            }

            $CadenaWhere = implode(" AND ", $where);
            $CadenaCondional = implode(" AND ", $condicional);

            $this->sql = $CadenaDelete . "WHERE ".$CadenaCondional.";";

            $CadenaDelete .= "WHERE ".$CadenaWhere.";";

            // echo $CadenaDelete;
            $this->result = $this->pdo->prepare($CadenaDelete);
            $this->result->execute($condicion);
            // var_dump($this->result->errorCode());
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
            return $this->result;
        } catch(PDOException $e) {
            // echo 'Error: ' . $e->getMessage();
            
            $this->errorCode = $this->result->errorCode();
            $this->errorInfo = $this->result->errorInfo();
        }
    }

    public function sql() {
        return $this->sql;
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function NumRows() {
        return count($this->result->fetchAll());
    }

    public function liberar() {
        $this->result = null;
        $this->pdo = null;
    
    }

    public function getPDO() {
        return $this->pdo;
    }

    public function errorCode() {
        return $this->errorCode;
    }

    public function errorInfo() {
        return $this->errorInfo;
    }

    // public ObtenerStringWhere($query) {
    //     $CadenaWhere = "";
    //     $array = preg_split("/WHERE/i", $query); // la i al final indica que no importar si es mayuscula o minuscula
    //     $where = $array[1];
    //     $condiciones = preg_split("/AND/i", $where);

    //     if(count($condiciones) > 0) {
    //         for ($i=0; $i < count($condiciones); $i++) { 
    //             $campo = explode("=", $condiciones);
    //             # code...
    //         }
    //     }
    //     return $CadenaWhere;
    // }

}

// $cadena = "campo < valor";
// $arr = preg_split("/[=,<,>,<=,>=,<>]/", $cadena);
// echo "<pre>";
// print_r($arr); exit;
$model = new ClsModel();
// $hola = 'hola';
// $model->insertar("admin.sucursales", array(":codsuc" => 1, ":descripcion" => $hola));
// $model->modificar("admin.sucursales", array(":descripcion" => 1, ":codemp" => 2), array(":codsuc" => 1, ":codemp" => 2));
// $model->eliminar("admin.sucursales",  array(":codsuc" => 1, ":codemp" => 2));
// echo $model->Sql();
// print_r($model);
?>