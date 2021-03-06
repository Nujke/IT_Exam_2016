<?php
/**
 * Created by PhpStorm.
 * User: Mike
 * Date: 26-Apr-16
 * Time: 19:03
 */
require("functions.php");
function create_user($people_id, $username, $password, $userrights_id){
    global $mysqli;
    $error = array();

    if(empty($people_id) || empty($username) || empty($password) || empty($userrights_id)){
         array_push($error, "All fields must be filled");
    }

    if(strlen($password) < 8){
        array_push($error, "Password has to be at least 8 characters");
    }

    $stmt = $mysqli->prepare("SELECT id FROM people WHERE id = ?");
    $stmt->bind_param("i", $people_id);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows() == 0){
        array_push($error, "No person with that ID");
        return $error;
    }
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT id FROM users WHERE people_id = ?");
    $stmt->bind_param("i", $people_id);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows() > 0){
        array_push($error, "There is already a user with that ID");
    }
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows() > 0){
        array_push($error, "There is already a user with that username");
    }
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT id FROM user_rights WHERE id = ?");
    $stmt->bind_param("i", $userrights_id);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows() == 0){
        array_push($error, "No user_right with that id");
    }
    $stmt->close();

    if(count($error) == 0){
        $password = generate_hash($password);
        $query = "INSERT INTO users (username, password, user_rights_id, people_id) VALUES (?,?,?,?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssii', $username, $password, $userrights_id, $people_id);
        if(!$stmt->execute()){
            $stmt->close();
            array_push($error, "Something went wrong when creating user. Please contact a system administrator");
            return $error;
        }
    }else{
        return $error;
    }
    $stmt->close();
    return true;


}

if(isset($_SESSION["id"])){
    $can_create_user = get_user_rights($_SESSION["id"])["create_user"];
    if($can_create_user){
        top("Admin - Create user");
        if(empty($_POST)) {
            ?>
            <form action="" method="post">
                <table>
                <tr>
                    <td><label for="username">Username</label></td>
                    <td><input type="text" name="username" id="username" placeholder="Username"></td>
                </tr>
                <tr>
                    <td><label for="password">Password</label></td>
                    <td><input type="password" name="password" id="password" placeholder="Password"></td>
                </tr>
                <tr>
                    <td><label for="people_id">Person</label></td>
                    <td>
                        <select name="people_id">
                            <?php
                            $stmt = $mysqli->prepare("SELECT first_name, last_name, id FROM people");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while($row = $result->fetch_assoc()){
                                ?>
                                <option value="<?php echo $row["id"]?>">
                                    <?php echo $row["first_name"]." ". $row["last_name"]?>
                                </option>
                                <?php
                            }
                            $stmt->close();
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>User rights preset name</td>
                    <td>
                        <select name="userrights_id">
                            <?php
                            $stmt = $mysqli->prepare("SELECT preset_name, id FROM user_rights");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while($row = $result->fetch_assoc()){
                                ?>
                                <option value="<?php echo $row["id"]?>"><?php echo $row["preset_name"]?></option>
                                <?php
                            }
                            $stmt->close();
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="submit" value="Create user"></td>
                </tr>
                </table>
            </form>
            <?php
        }else{
            $create_user_response = create_user($_POST["people_id"],
                $_POST["username"],
                $_POST["password"],
                $_POST["userrights_id"]);
            if($create_user_response === true){
                echo "<h1>SUCCESS</h1>";
                log_event("USER_CREATE", 1, $_SERVER["REMOTE_ADDR"], $_SESSION["id"], $_POST["people_id"]);
            }elseif(is_array($create_user_response)){
                log_event("USER_CREATE", 0, $_SERVER["REMOTE_ADDR"], $_SESSION["id"], $_POST["people_id"]);
                echo "<ul>";
                foreach($create_user_response as $error){
                    echo "<li>".$error."</li>";
                }
                echo "</ul>";
            }
        }
        $mysqli->close();

        ?>
        </body>
    </html>
    <?php
    }else{
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
        header("Location: not_found.php");
    }
}else{
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
    header("Location: not_found.php");
}
?>