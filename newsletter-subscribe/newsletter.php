<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$PRINT_ERRORS = true;

function init_db($db_host, $db_user, $db_password, $db_name) {
    $db;
    try {
        $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
        $db->exec('SET CHARACTER SET utf8');
        // Use real prepared statements instead of pdo emulated prepare statements
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method".__METHOD__.": ".$e->getMessage()."\n";
        }
        
        return false;
    }
    return $db;
}

function table_exists($db, $table_name) {
    try {
        $result = $db->query("SELECT 1 FROM $table_name LIMIT 1");
    } catch (Exception $e) {
        return false;
    }
    return $result !== false;
}

function create_newsletter_table($db, $table_name) {    
    // Create newsletter table when table not exists
    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
        `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `email` VARCHAR(96) NOT NULL,
        `language` varchar(3),
        `confirmation_code` VARCHAR(96),
        `unsubscribe_code` VARCHAR(96),
        `datetime_confirm` DATETIME DEFAULT NULL,
        `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `date_updated` DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) CHARSET=utf8";
    $db->exec($sql);
}

function add_newsletter_entry($db, $table_name, $email, $language, $confirmation_code, $unsubscribe_code) {
    $sql = "INSERT INTO `$table_name` (
        `email`, `language`, `confirmation_code`, `unsubscribe_code`
    )
    VALUES (
        :email, :language, :confirmation_code, :unsubscribe_code
    )";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method '".__METHOD__."':";
            echo "\n Prepare failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':language', $language);
    $stmt->bindParam(':confirmation_code', $confirmation_code);
    $stmt->bindParam(':unsubscribe_code', $unsubscribe_code);
    $result = $stmt->execute();
    if (!$result) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method:'".__METHOD__."':";
            echo "\n Execute failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    return true;
}

function confirm_email_address($db, $table_name, $confirmation_code, $days_to_confirm) {
    $sql = "UPDATE `$table_name`
        SET 
        `datetime_confirm` = NOW(),
        `confirmation_code` = NULL
        WHERE 
        `confirmation_code` = :confirmation_code
        AND 
        `date_added` >= NOW() - INTERVAL $days_to_confirm DAY";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            if ($GLOBALS['PRINT_ERRORS'] === true) {
                echo "\n ERROR in method:'".__METHOD__."':";
                echo "\n Prepare failed PDO::errorInfo():\n";
                print_r($db->errorInfo());
            }
            return false;
        }
        $stmt->bindParam(':confirmation_code', $confirmation_code);
        $result = $stmt->execute();
        if (!$result) {
            if ($GLOBALS['PRINT_ERRORS'] === true) {
                echo "\n ERROR in method:'".__METHOD__."':";
                echo "\n Execute failed PDO::errorInfo():\n";
                print_r($db->errorInfo());
            }
            return false;
        }
        if ($stmt->rowCount() < 1) {
            if ($GLOBALS['PRINT_ERRORS'] === true) {
                echo "\n ERROR in method:'".__METHOD__."':";
                echo "\n Row count is < 1 after performing action\n";
            }
            return false;
        }
        return true;
}

function unsubscribe_by_email($db, $table_name, $email) {
    $sql = "DELETE FROM `$table_name`
        WHERE
        `email` = :email";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method:'".__METHOD__."':";
            echo "\n Prepare failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    $stmt->bindParam(':email', $email);
    $result = $stmt->execute();
    if (!$result) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method:'".__METHOD__."':";
            echo "\n Execute failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    return true;
}

function unsubscribe_by_uid($db, $table_name, $u_id) {
    $sql = "DELETE FROM `$table_name`
        WHERE
        `unsubscribe_code` = :u_id";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method:'".__METHOD__."':";
            echo "\n Prepare failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    $stmt->bindParam(':u_id', $u_id);
    $result = $stmt->execute();
    if (!$result) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method:'".__METHOD__."':";
            echo "\n Execute failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    return true;
}

function clean_newsletter_table($db, $table_name, $days_to_confirm) {
    if (!table_exists($db, $table_name)) {
        return false;
    }
    $sql = "DELETE FROM `$table_name`
        WHERE
        `date_added` < NOW() - INTERVAL $days_to_confirm DAY
        AND 
        `confirmation_code` IS NOT NULL";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method:'".__METHOD__."':";
            echo "\n Prepare failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    $result = $stmt->execute();
    if (!$result) {
        if ($GLOBALS['PRINT_ERRORS'] === true) {
            echo "\n ERROR in method:'".__METHOD__."':";
            echo "\n Execute failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
        }
        return false;
    }
    return true;
}

function send_confirmation_email($email, $email_from, $reply_to, $confirmation_code) {
    global $ROOT_PATH;
    // Split url into base url and get params 
    $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
    $url = "https://".$_SERVER['HTTP_HOST'].$uri_parts[0];
    // Send confirmation email
    $subject = file_get_contents($ROOT_PATH."templates/subject.txt");
    $confirmation_url = $url . '?c_id=' . $confirmation_code;
    $message = file_get_contents($ROOT_PATH."templates/body.html");
    $message = str_replace("{confirmation_url}", $confirmation_url, $message);
    $headers = 'MIME-Version: 1.0' . "\r\n" .
        'Content-type: text/html; charset=utf-8' . "\r\n" .
        "From: $email_from" . "\r\n" .
        "Reply-To: $reply_to" . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
        // Send email to customer
    mail($email, $subject, $message, $headers);
}
$success = false;
$NEWSLETTER_TABLE_NAME = "newsletter";

$ROOT_PATH = __DIR__."/";

$CONFIG = parse_ini_file($ROOT_PATH.'config/config.ini', true);
$EMAIL_FROM = $CONFIG['GENERAL']['email_from'];
$EMAIL_REPLY_TO = $CONFIG['GENERAL']['email_repy_to'];
$DAYS_TO_CONFIRM = $CONFIG['GENERAL']['time_to_confirm'];

$DB_HOST = $CONFIG['DATABASE']['host'];
$DB_USER = $CONFIG['DATABASE']['user'];
$DB_PASSWORD = $CONFIG['DATABASE']['password'];
$DB_NAME = $CONFIG['DATABASE']['name'];
$db = init_db($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
if ($db === false) {
    $success = false;
    echo 'error';
}
// Clean Newsletter table at every call
clean_newsletter_table($db, $NEWSLETTER_TABLE_NAME, $DAYS_TO_CONFIRM);


if (isset($_GET['subscribe']) && isset($_POST['email'])) {
    $email = $_POST['email'];
    if (!is_null($email) && $email !== "") {
        $language = 'de';
        $confirmation_code = md5("" . uniqid() . random_int(0, 9999999));
        $unsubscribe_code = md5("" . uniqid() . random_int(0, 9999999));
        
        
        // Add email data to db
        $result = add_newsletter_entry($db, $NEWSLETTER_TABLE_NAME, $email, $language, $confirmation_code, $unsubscribe_code);
        if ($result === true) {
            // Send confirmation email
            send_confirmation_email($email, $EMAIL_FROM, $EMAIL_REPLY_TO, $confirmation_code);
            $success = true;
        }
    }
}
// Confirm email address
else if (isset($_GET['c_id'])) {
    $c_id = $_GET['c_id'];
    if (!is_null($c_id) && $c_id !== "") {
        $result = confirm_email_address($db, $NEWSLETTER_TABLE_NAME, $c_id, $DAYS_TO_CONFIRM);
        if ($result === true) {
            $success = true;
        }
    }
}
// Unsubscripe by email
else if(isset($_GET['unsubscribe']) && isset($_POST['email'])) {
    $email = $_POST['email'];
    if (!is_null($email) && $email !== "") {
        $result = unsubscribe_by_email($db, $NEWSLETTER_TABLE_NAME, $email);
        if ($result === true) {
            $success = true;
        }
    }
}
// Unsubscribe by unsubscribtion code
else if (isset($_GET['u_id'])) {
    $u_id = $_GET['u_id'];
    if (!is_null($u_id) && $u_id !== "") {
        $result = unsubscribe_by_uid($db, $NEWSLETTER_TABLE_NAME, $u_id);
        if ($result === true) {
            $success = true;
        }
    }
}
else if(isset($_GET['installdb'])) {
    create_newsletter_table($db, $NEWSLETTER_TABLE_NAME);
    $success = true;
}

if (!$success) {
    echo 'error';
    // header('HTTP/1.1 500 Internal Server Error');
    // header('Content-Type: application/json; charset=UTF-8');
    // die(json_encode(array('message' => 'ERROR', 'code' => 1337)));
}
else {
    echo 'success';
}


?>