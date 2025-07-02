# test.py - AWS Music Subscription System Infrastructure Setup
# This script creates DynamoDB tables, loads sample data into the tables, and uploads images to an S3 bucket
# WARNING: This script will delete ALL existing DynamoDB tables and S3 buckets in your AWS account
# Only run this in a development/testing environment
import json
import time

import boto3
import requests
from botocore.config import Config


# Create a default session
session = boto3.Session(region_name = 'us-east-1')

# Create a DynamoDB client
dynamodb = session.resource("dynamodb")


# Delete existing tables
def delete_tables():
    try:
        table_names = [table.name for table in dynamodb.tables.all()]
        for table_name in table_names:
            table = dynamodb.Table(table_name)
            try:
                table.delete()
                print(f"Deleting table: {table_name}")
                table.wait_until_not_exists()
                print(f"Deleted table: {table_name}")
            except dynamodb.meta.client.exceptions.ResourceInUseException:
                print(f"Table {table_name} is being deleted. Waiting...")
                time.sleep(5)  # Wait for a few seconds before checking again
                table.wait_until_not_exists()
                print(f"Deleted table: {table_name}")
    except Exception as e:
        print(f"Error deleting tables: {str(e)}")

delete_tables()

# Create an S3 client
s3 = session.client("s3")


# Delete existing objects and buckets
def delete_s3_objects_and_buckets():
    try:
        buckets = s3.list_buckets()["Buckets"]
        for bucket in buckets:
            bucket_name = bucket["Name"]
            objects = s3.list_objects(Bucket=bucket_name).get("Contents", [])
            for obj in objects:
                s3.delete_object(Bucket=bucket_name, Key=obj["Key"])
                print(f"Deleted object: {obj['Key']} from bucket: {bucket_name}")
            s3.delete_bucket(Bucket=bucket_name)
            print(f"Deleted bucket: {bucket_name}")
    except Exception as e:
        print(f"Error deleting S3 objects and buckets: {str(e)}")


delete_s3_objects_and_buckets()

# Define the "login" table name
login_table_name = "login"

# Create the "login" table
login_table = dynamodb.create_table(
    TableName=login_table_name,
    AttributeDefinitions=[{"AttributeName": "email", "AttributeType": "S"}],
    KeySchema=[{"AttributeName": "email", "KeyType": "HASH"}],
    ProvisionedThroughput={"ReadCapacityUnits": 5, "WriteCapacityUnits": 5},
)

# Wait for the "login" table to be created
login_table.wait_until_exists()
print(f"Table '{login_table_name}' created successfully.")

# Define the "subscriptions" table name
subscriptions_table_name = "subscriptions"

# Create the "subscriptions" table
subscriptions_table = dynamodb.create_table(
    TableName=subscriptions_table_name,
    AttributeDefinitions=[
        {"AttributeName": "email", "AttributeType": "S"},
        {"AttributeName": "musicId", "AttributeType": "S"},
    ],
    KeySchema=[
        {"AttributeName": "email", "KeyType": "HASH"},
        {"AttributeName": "musicId", "KeyType": "RANGE"},
    ],
    ProvisionedThroughput={"ReadCapacityUnits": 5, "WriteCapacityUnits": 5},
)

# Wait for the "subscriptions" table to be created
subscriptions_table.wait_until_exists()
print(f"Table '{subscriptions_table_name}' created successfully.")

# Add sample items to login table - replace with your own test data
items = [
    {'email': 'user1@example.com', 'user_name': 'TestUser1', 'password': '012345'},
    {'email': 'user2@example.com', 'user_name': 'TestUser2', 'password': '123456'},
    {'email': 'user3@example.com', 'user_name': 'TestUser3', 'password': '234567'},
    {'email': 'user4@example.com', 'user_name': 'TestUser4', 'password': '345678'},
    {'email': 'user5@example.com', 'user_name': 'TestUser5', 'password': '456789'},
    {'email': 'user6@example.com', 'user_name': 'TestUser6', 'password': '567890'},
    {'email': 'user7@example.com', 'user_name': 'TestUser7', 'password': '678901'},
    {'email': 'user8@example.com', 'user_name': 'TestUser8', 'password': '789012'},
    {'email': 'user9@example.com', 'user_name': 'TestUser9', 'password': '890123'},
    {'email': 'user10@example.com', 'user_name': 'TestUser10', 'password': '901234'},
]
for item in items:
    login_table.put_item(Item=item)

print("Items added to the 'login' table.")

# Define the "music" table name
music_table_name = "music"

# Create the "music" table with the "user_email-index" GSI
music_table = dynamodb.create_table(
    TableName=music_table_name,
    AttributeDefinitions=[
        {"AttributeName": "title", "AttributeType": "S"},
        {"AttributeName": "artist", "AttributeType": "S"},
        {"AttributeName": "user_email", "AttributeType": "S"}
    ],
    KeySchema=[
        {"AttributeName": "title", "KeyType": "HASH"},
        {"AttributeName": "artist", "KeyType": "RANGE"},
    ],
    GlobalSecondaryIndexes=[
        {
            "IndexName": "user_email-index",
            "KeySchema": [
                {"AttributeName": "user_email", "KeyType": "HASH"}
            ],
            "Projection": {
                "ProjectionType": "ALL"
            },
            "ProvisionedThroughput": {
                "ReadCapacityUnits": 5,
                "WriteCapacityUnits": 5
            }
        }
    ],
    ProvisionedThroughput={"ReadCapacityUnits": 5, "WriteCapacityUnits": 5},
)

# Wait for the "music" table to be created
music_table.wait_until_exists()
print(f"Table '{music_table_name}' created successfully.")

# Read the JSON file
with open('a1.json') as file:
    data = json.load(file)

# Create a new S3 bucket with a unique name - replace with your own unique bucket name
bucket_name = "music-app-images-bucket-unique"  # Replace with your unique bucket name
s3.create_bucket(Bucket=bucket_name)
print(f"S3 bucket '{bucket_name}' created successfully.")

# Iterate over each item in the data
for item in data["songs"]:
    title = item["title"]
    artist = item["artist"]
    year = item["year"]
    web_url = item["web_url"]
    img_url = item["img_url"]

    # Download the image from the URL
    response = requests.get(img_url)

    if response.status_code == 200:
        # Extract the image filename from the URL
        image_filename = img_url.split("/")[-1]

        # Upload the image to S3
        s3.put_object(Bucket=bucket_name, Key=image_filename, Body=response.content)
        print(f"Image '{image_filename}' uploaded to S3 bucket '{bucket_name}'.")

        # Update the image URL to point to the S3 bucket
        img_url = f"https://{bucket_name}.s3.amazonaws.com/{image_filename}"

    # Create a new item in the "music" table
    music_table.put_item(
        Item={
            "title": title,
            "artist": artist,
            "year": year,
            "web_url": web_url,
            "img_url": img_url,
        }
    )

print("Data loaded successfully into the 'music' table.")
