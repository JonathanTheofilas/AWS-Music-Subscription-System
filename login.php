// Purpose: This file is used to register a new user. It checks if the email already exists in the database and if not, it adds the new user to the database.

<?php
require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;

$dynamodb = new DynamoDbClient([
    'region' => 'us-east-1',
    'version' => 'latest'
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $dynamodb->getItem([
        'TableName' => 'login',
        'Key' => [
            'email' => ['S' => $email]
        ]
    ]);

    if (isset($result['Item']) && $result['Item']['password']['S'] === $password) {
        session_start();
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $result['Item']['user_name']['S'];
        header('Location: main.php');
        exit();
    } else {
        $error_message = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
        <input type="submit" value="Login">
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>