// Purpose: This file is used to register a new user. It checks if the email already exists in the database and if not, it adds the new user to the database.

<?php
require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;

$dynamodb = new DynamoDbClient([
    'region' => 'us-east-1', #Use your preferred/configured region here
    'version' => 'latest'
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $username = $_POST['user_name'];
    $password = $_POST['password'];

    $result = $dynamodb->getItem([
        'TableName' => 'login',
        'Key' => [
            'email' => ['S' => $email]
        ]
    ]);

    if (isset($result['Item'])) {
        $error_message = 'Email already exists';
    } else {
        $dynamodb->putItem([
            'TableName' => 'login',
            'Item' => [
                'email' => ['S' => $email],
                'user_name' => ['S' => $username],
                'password' => ['S' => $password]
            ]
        ]);
        header('Location: login.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h1>Register</h1>
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>
        <label for="user_name">Username:</label>
        <input type="text" id="user_name" name="user_name" required><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
        <input type="submit" value="Register">
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
