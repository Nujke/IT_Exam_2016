<?php
/**
 * Created by PhpStorm.
 * User: Mike
 * Date: 26-Apr-16
 * Time: 19:06
 */
session_start();
require("config.php");

if(isset($_SESSION["id"])){
    if(is_first_time_login($_SESSION["id"]) == 1 && !isset($_GET["first_time"])){
        header("Location:change_password.php?first_time=1");
    }
}

function is_first_time_login($id){
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT first_time_login FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($first_time_login);
    $stmt->fetch();
    $stmt->close();
    return $first_time_login;
}

function generate_hash($password){
    $options = [
        'cost' => 11,
        'salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM),
    ];
    return password_hash($password, PASSWORD_BCRYPT, $options);
}

function top($title){
    ?>
    <html>
    <head>
        <title><?php echo $title?></title>
        <link rel="stylesheet" type="text/css" href="style.css">
    </head>
    <body>
        <nav>
    <?php
    if(isset($_SESSION["id"])) {
        $id = $_SESSION["id"];
        if (get_user_rights($id)["create_user"]) {
            echo '<a href="create_user.php">Create user</a> ';
        }
        if (get_user_rights($id)["view_users"]) {
            echo '<a href="view_users.php">View users</a> ';
        }
        if (get_user_rights($id)["create_person"]) {
            echo '<a href="create_person.php">Create person</a> ';
        }
        if (get_user_rights($id)["create_case"]) {
            echo '<a href="create_case.php">Create case</a> ';
        }
        if (get_user_rights($id)["manage_user_rights_presets"]) {
            echo '<a href="manage_user_rights_presets.php">Manage user rights presets</a> ';
        }
        if (get_user_rights($id)["view_logs"]) {
            echo '<a href="view_logs.php">View logs</a> ';
        }
            echo '<a href="logout.php">Logout</a>';
    }else{
        echo '<a href="login.php">Login</a> ';
    }
    ?>
        </nav>
        <div id="content">
    <?php
}

function log_event($action, $response, $ip, $user_id, $people_id){
    global $mysqli;
    $error = array();
    $query = "INSERT INTO logs (action, response, ip, users_id, people_id) VALUES (?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sisii', $action, $response, $ip, $user_id, $people_id);
    if(!$stmt->execute()){
        $stmt->close();
        array_push($error, "Error code: #1");
        return $error;
    }
}

function decrypt_cpr($cpr){
    if(empty($cpr)){
        return false;
    }
    $parts = explode(':', $cpr);
    $cpr = $parts[1];
    $iv = $parts[0];
    $password = "39849f50df275b2cd3907631aaa8809c";

    $cpr = openssl_decrypt($cpr, "AES-256-CBC", $password, 0, $iv);
    return $cpr;
}

function encrypt_cpr($cpr){
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-256-cbc"));
    $password = "39849f50df275b2cd3907631aaa8809c";
    $cpr = openssl_encrypt($cpr, "aes-256-cbc", $password, 0, $iv);
    return $iv.":".$cpr;
}
function get_user_rights($user_id){
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT user_rights.* FROM user_rights
                              LEFT JOIN users
                              ON user_rights.id=users.user_rights_id
                              WHERE users.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array(MYSQLI_ASSOC);
    $stmt->close();
    return $row;
}
?>