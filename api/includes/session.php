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
        $id = mysqli_real_escape_string($this->link, $id);
        $sql = "SELECT data FROM sessions WHERE id = '$id'";
        $result = mysqli_query($this->link, $sql);
        if($result && $row = mysqli_fetch_assoc($result)){
            return $row['data'];
        }
        return "";
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        $id = mysqli_real_escape_string($this->link, $id);
        $data = mysqli_real_escape_string($this->link, $data);
        $access = time();
        $sql = "REPLACE INTO sessions (id, data, access) VALUES ('$id', '$data', '$access')";
        return (bool)mysqli_query($this->link, $sql);
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $id = mysqli_real_escape_string($this->link, $id);
        $sql = "DELETE FROM sessions WHERE id = '$id'";
        return (bool)mysqli_query($this->link, $sql);
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        $old = time() - $maxlifetime;
        $sql = "DELETE FROM sessions WHERE access < $old";
        return (bool)mysqli_query($this->link, $sql);
    }
}

if($link){
    $handler = new DBSessionHandler($link);
    session_set_save_handler($handler, true);
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    } catch (Exception $e) {
        // Fallback or ignore
    }
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
