<?php
function createNewsletterTable($db) {
    // Create newsletter table when table not exists
    $sql = "CREATE TABLE IF NOT EXISTS newsletter (
        id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(96) NOT NULL,
        language varchar(3),
        confirmation_code VARCHAR(96),
        datetime_confirm DATETIME DEFAULT NULL,
        date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_updated DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) CHARSET=utf8";
    $db->exec($sql);
}

function addEmailEntry($db, $email, $language, $confirmationCode) {
    $sql = "INSERT INTO newsletter (
        email, language, confirmation_code
    )
    VALUES (
        :email, :language, :confirmation_code
    )";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo "\n Prepare failed PDO::errorInfo():\n";
        print_r($db->errorInfo());
        return false;
    }
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':language', $language);
    $stmt->bindParam(':confirmation_code', $confirmationCode);
    $result = $stmt->execute();
    if (!$result) {
        echo "\n Execute failed PDO::errorInfo():\n";
        print_r($db->errorInfo());
        return false;
    }
    return true;
}

function confirmEmailAdress($db, $confirmationCode) {
    $sql = "UPDATE newsletter
        SET 
        datetime_confirm = NOW(),
        confirmation_code = NULL
        WHERE 
        confirmation_code = :confirmation_code";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            echo "\n Prepare failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
            return false;
        }
        $stmt->bindParam(':confirmation_code', $confirmationCode);
        $result = $stmt->execute();
        if (!$result) {
            echo "\n Execute failed PDO::errorInfo():\n";
            print_r($db->errorInfo());
            return false;
        }
        if ($stmt->rowCount() < 1) {
            return false;
        }
        return true;
}
$CONFIG = parse_ini_file(__DIR__ . '/config/config.ini', true);
// Connect to db
$dbHost = $CONFIG['DATABASE']['host'];
$dbUser = $CONFIG['DATABASE']['user'];
$dbPassword = $CONFIG['DATABASE']['password'];
$dbName = $CONFIG['DATABASE']['name'];
$db;
try {
    $db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    $db->exec('SET CHARACTER SET utf8');
    // Use real prepared statements instead of pdo emulated prepare statements
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    print "\n ERROR in " . __METHOD__ . ": " . $e->getMessage() . "\n";
}

createNewsletterTable($db);
?>
<?php if (!isset($_GET['c_id'])) { ?>
    <form id="add_comment_form" method=post>
        <input name="email" type="text" placeholder="Email" />
        <input id=submit name="register_email" type="submit" value="Abschicken">
    </form>
<?php
    if (isset($_POST['email'])) {
        echo "Send confirmation mail<br>";
        $email = $_POST['email'];
        $language = 'de';
        $confirmationCode = md5("" . uniqid() . random_int(0, 9999999));
        
        $from = $CONFIG['GENERAL']['email_from'];
        $replyTo = $CONFIG['GENERAL']['email_repy_to'];
        $url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        // Add email data to db
        addEmailEntry($db, $email, 'de', $confirmationCode);
        // Send confirmation email
        $subject = 'Subscribe for newsletter';
        $confirmUrl = $url . '?c_id=' . $confirmationCode;
        $message = "Confirm email: <a href='$confirmUrl'>$confirmUrl</a>";
        $headers = 'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=utf-8' . "\r\n" .
            "From: $from" . "\r\n" .
            "Reply-To: $from" . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
            // Send email to customer
            mail($email, $subject, $message, $headers);
    }

} 
if (isset($_GET['c_id'])) {
    $cId = $_GET['c_id'];
    $result = confirmEmailAdress($db, $cId);
    if (!$result) {
        echo "Cannot confirm email<br>";
    }
    else {
        echo "Email confirmed!<br>";
    }
}
?>