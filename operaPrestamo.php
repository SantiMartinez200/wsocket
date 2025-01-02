<?php

class operaPrestamo {
    public $servername = "localhost";
	public $username = "root";
	public $password = "";
	public $dbname = "websocket";

    public function connect(){
        $conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }

    public function aceptar($userid){
        $conn = $this->connect();
        $sql = "UPDATE prestamos SET confirmado = 'SI' WHERE userid = '$userid'";
        if($conn->exec($sql)){
            return true;
        };
    }

    public function rechazar($userid){
        $conn = $this->connect();
        $sql = "UPDATE prestamos SET confirmado = 'NO' WHERE userid = '$userid'";
        if($conn->exec($sql)){
            return true;
        };
    }
}