// Purpose: Main page for the application, where users can view subscribed music and query music to subscribe to.

<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}

require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;

// Retrieve user information from the session
$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'];

// Create DynamoDB client
$dynamodb = new DynamoDbClient([
    'region' => 'us-east-1', #Use your preferred/configured region here
    'version' => 'latest'
]);

// Create S3 client
$s3Client = new S3Client([
    'profile' => 'default',
    'region' => 'us-east-1',
    'version' => 'latest'
]);

$bucketName = 'images-placeholder-bucket'; #Replace this with your actual bucket name

// Handle form submission for music query
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title']) || isset($_POST['artist']) || isset($_POST['year'])) {
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $artist = isset($_POST['artist']) ? $_POST['artist'] : '';
        $year = isset($_POST['year']) ? $_POST['year'] : '';

        if (empty($title) && empty($artist) && empty($year)) {
            $query_message = 'Please provide at least one search criteria.';
        } else {
            $query_params = [
                'TableName' => 'music',
                'FilterExpression' => '',
                'ExpressionAttributeNames' => [],
                'ExpressionAttributeValues' => []
            ];

            if (!empty($title)) {
                $query_params['FilterExpression'] .= '#title = :title';
                $query_params['ExpressionAttributeNames']['#title'] = 'title';
                $query_params['ExpressionAttributeValues'][':title'] = ['S' => $title];
            }

            if (!empty($artist)) {
                if (!empty($query_params['FilterExpression'])) {
                    $query_params['FilterExpression'] .= ' AND ';
                }
                $query_params['FilterExpression'] .= '#artist = :artist';
                $query_params['ExpressionAttributeNames']['#artist'] = 'artist';
                $query_params['ExpressionAttributeValues'][':artist'] = ['S' => $artist];
            }

            if (!empty($year)) {
                if (!empty($query_params['FilterExpression'])) {
                    $query_params['FilterExpression'] .= ' AND ';
                }
                $query_params['FilterExpression'] .= '#year = :year';
                $query_params['ExpressionAttributeNames']['#year'] = 'year';
                $query_params['ExpressionAttributeValues'][':year'] = ['N' => $year];
            }

            $query_result = $dynamodb->scan($query_params);

            if (!empty($query_result['Items'])) {
                $query_results = $query_result['Items'];
                foreach ($query_results as &$music) {
                    $music['img_url'] = get_artist_image_url($music['artist']['S'], $s3Client, $bucketName);
                }
            } else {
                $query_message = 'No results found. Please try again.';
            }
        }
    } elseif (isset($_POST['unsubscribe_title']) && isset($_POST['unsubscribe_artist'])) {
        $title = $_POST['unsubscribe_title'];
        $artist = $_POST['unsubscribe_artist'];

        $dynamodb->deleteItem([
            'TableName' => 'subscriptions',
            'Key' => [
                'email' => ['S' => $user_email],
                'musicId' => ['S' => $title . '_' . $artist]
            ]
        ]);
    } elseif (isset($_POST['subscribe_title']) && isset($_POST['subscribe_artist'])) {
        $title = $_POST['subscribe_title'];
        $artist = $_POST['subscribe_artist'];

        $result = $dynamodb->getItem([
            'TableName' => 'music',
            'Key' => [
                'title' => ['S' => $title],
                'artist' => ['S' => $artist]
            ]
        ]);

        if (isset($result['Item'])) {
            $music = $result['Item'];
            $music['email'] = ['S' => $user_email];
            $music['musicId'] = ['S' => $title . '_' . $artist];

            try {
                $dynamodb->putItem([
                    'TableName' => 'subscriptions',
                    'Item' => $music,
                    'ConditionExpression' => 'attribute_not_exists(musicId)'
                ]);
                $success_message = 'Music subscribed successfully!';
                $subscribed_music = get_subscribed_music($dynamodb, $user_email, $s3Client, $bucketName);
            } catch (Exception $e) {
                if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                    $error_message = 'You have already subscribed to this music.';
                } else {
                    $error_message = 'An error occurred while subscribing. Please try again.';
                }
            }
        }
    }
}

function get_subscribed_music($dynamodb, $user_email, $s3Client, $bucket_name) {
    $query_params = [
        'TableName' => 'subscriptions',
        'KeyConditionExpression' => '#email = :email',
        'ExpressionAttributeNames' => [
            '#email' => 'email'
        ],
        'ExpressionAttributeValues' => [
            ':email' => ['S' => $user_email]
        ]
    ];

    $query_result = $dynamodb->query($query_params);

    if (!empty($query_result['Items'])) {
        $subscribed_music = $query_result['Items'];
        foreach ($subscribed_music as &$music) {
            $music['img_url'] = get_artist_image_url($music['artist']['S'], $s3Client, $bucket_name);
        }
        return $subscribed_music;
    }

    return [];
}

function get_artist_image_url($artist, $s3Client, $bucketName) {
    $image_filename = str_replace(' ', '', $artist) . '.jpg';
    return "https://$bucket_name.s3.amazonaws.com/$image_filename";
}

$subscribed_music = get_subscribed_music($dynamodb, $user_email, $s3Client, $bucketName);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Main Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo $user_name; ?>!</h1>
    <p>Your email: <?php echo $user_email; ?></p>
    <a href="logout.php">Logout</a>

    <h2>Subscribed Music</h2>
    <?php if (!empty($subscribed_music)): ?>
        <ul>
        <?php foreach ($subscribed_music as $music): ?>
            <li>
                <?php echo $music['title']['S'] . ' - ' . $music['artist']['S'] . ' (' . $music['year']['N'] . ')'; ?>
                <img src="<?php echo $music['img_url']; ?>" alt="<?php echo $music['title']['S']; ?> Cover">
                <form method="POST" action="">
                    <input type="hidden" name="unsubscribe_title" value="<?php echo $music['title']['S']; ?>">
                    <input type="hidden" name="unsubscribe_artist" value="<?php echo $music['artist']['S']; ?>">
                    <input type="submit" value="Remove">
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No subscribed music found.</p>
    <?php endif; ?>

    <h2>Query Music</h2>
    <form method="POST" action="">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title"><br>
        <label for="artist">Artist:</label>
        <input type="text" id="artist" name="artist"><br>
        <label for="year">Year:</label>
        <input type="text" id="year" name="year"><br>
        <input type="submit" value="Query">
    </form>

    <?php if (isset($query_results)): ?>
        <h3>Query Results</h3>
        <ul>
        <?php foreach ($query_results as $music): ?>
            <li>
                <?php echo $music['title']['S'] . ' - ' . $music['artist']['S'] . ' (' . $music['year']['N'] . ')'; ?>
                <img src="<?php echo $music['img_url']; ?>" alt="<?php echo $music['title']['S']; ?> Cover">
                <form method="POST" action="">
                    <input type="hidden" name="subscribe_title" value="<?php echo $music['title']['S']; ?>">
                    <input type="hidden" name="subscribe_artist" value="<?php echo $music['artist']['S']; ?>">
                    <input type="submit" value="Subscribe">
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php elseif (isset($query_message)): ?>
        <p><?php echo $query_message; ?></p>
    <?php endif; ?>
</body>
</html>
