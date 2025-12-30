<?php
require_once __DIR__ . '/config.php';

class DBSessionHandler implements SessionHandlerInterface {
    private $link;

    public function __construct($link) {
        $this->link = $link;
    }

    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName) {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id) {
        $sql = "SELECT data FROM sessions WHERE id = ?";
        if($stmt = mysqli_prepare($this->link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $id);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $data);
                    mysqli_stmt_fetch($stmt);
                    return $data;
                }
            }
            mysqli_stmt_close($stmt);
        }
        return "";
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        $access = time();
        $sql = "REPLACE INTO sessions (id, data, access) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($this->link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssi", $id, $data, $access);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        }
        return false;
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $sql = "DELETE FROM sessions WHERE id = ?";
        if($stmt = mysqli_prepare($this->link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $id);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        }
        return false;
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        $old = time() - $maxlifetime;
        $sql = "DELETE FROM sessions WHERE access < ?";
        if($stmt = mysqli_prepare($this->link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $old);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        }
        return false;
    }
}

$handler = new DBSessionHandler($link);
session_set_save_handler($handler, true);
session_start();
