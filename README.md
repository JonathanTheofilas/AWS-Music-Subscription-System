# AWS Music Subscription System

A full-stack cloud-based music subscription platform built with PHP and AWS services. Users can register, authenticate, search for music, and manage their personal music subscriptions with an intuitive web interface.

![AWS](https://img.shields.io/badge/AWS-232F3E?style=for-the-badge&logo=amazon-aws&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Python](https://img.shields.io/badge/Python-3776AB?style=for-the-badge&logo=python&logoColor=white)
![DynamoDB](https://img.shields.io/badge/DynamoDB-4053D6?style=for-the-badge&logo=amazon-dynamodb&logoColor=white)
![S3](https://img.shields.io/badge/S3-569A31?style=for-the-badge&logo=amazon-s3&logoColor=white)

## 🎵 Features

- **User Authentication**: Secure registration and login system
- **Music Discovery**: Advanced search functionality by title, artist, and year
- **Subscription Management**: Subscribe/unsubscribe to music with duplicate prevention
- **Image Integration**: Automated cover art management with S3 storage
- **Real-time Updates**: Dynamic content updates without page refresh
- **Scalable Architecture**: Built on AWS cloud infrastructure for high availability

## 🛠️ Tech Stack

**Backend:**
- PHP 7.4+ with AWS SDK
- Python 3.8+ for infrastructure setup
- AWS DynamoDB for data storage
- AWS S3 for image storage

**Frontend:**
- HTML5 & CSS3
- Vanilla JavaScript
- Responsive design

**Cloud Services:**
- Amazon DynamoDB (NoSQL database)
- Amazon S3 (Object storage)
- Amazon EC2 (Web hosting)

## 📋 Prerequisites

Before running this project, ensure you have:

- AWS Account with appropriate permissions
- AWS CLI configured with access credentials
- PHP 7.4+ installed
- Python 3.8+ installed
- Composer (PHP package manager)
- Web server (Apache/Nginx) or local development environment

## 🚀 Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/aws-music-subscription-system.git
cd aws-music-subscription-system
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Python Dependencies
```bash
pip install boto3 requests
```

### 4. Configure AWS Credentials
```bash
aws configure
# Enter your AWS Access Key ID, Secret Access Key, and preferred region (us-east-1)
```

### 5. Prepare Music Data
Ensure you have an `a1.json` file in the project root with the following structure:
```json
{
  "songs": [
    {
      "title": "Song Title",
      "artist": "Artist Name",
      "year": "2023",
      "web_url": "https://example.com",
      "img_url": "https://example.com/image.jpg"
    }
  ]
}
```

### 6. Run Infrastructure Setup
```bash
python test.py
```
This script will:
- Create DynamoDB tables (login, music, subscriptions)
- Set up S3 bucket for image storage
- Load initial data and download cover art
- Configure proper indexes and relationships

### 7. Deploy Web Application
Copy PHP files to your web server directory or run locally:
```bash
php -S localhost:8000
```

## 📁 Project Structure

```
aws-music-subscription-system/
├── test.py                # AWS infrastructure setup script
├── login.php              # User authentication page
├── register.php           # User registration page
├── main.php               # Main dashboard and music management
├── logout.php             # Session termination
├── a1.json                # Music data source file
└── README.md              # Project documentation
```

## 🗄️ Database Schema

### Tables

**login**
- Primary Key: `email` (String)
- Attributes: `user_name`, `password`

**music**
- Primary Key: `title` (String), `artist` (String)
- Global Secondary Index: `user_email-index`
- Attributes: `year`, `web_url`, `img_url`

**subscriptions**
- Primary Key: `email` (String), `musicId` (String)
- Attributes: Inherited from music table

## 💻 Usage

### Registration & Login
1. Navigate to the application URL
2. Register a new account or login with existing credentials
3. Access the main dashboard upon successful authentication

### Music Discovery
1. Use the search form to find music by:
   - Title (exact match)
   - Artist (exact match)
   - Year (exact match)
   - Any combination of the above
2. Browse search results with cover art
3. Subscribe to desired tracks

### Subscription Management
1. View all subscribed music on the main dashboard
2. Each subscription displays cover art and track information
3. Remove subscriptions using the "Remove" button
4. Duplicate subscription prevention built-in

## 🔧 Configuration

### AWS Resources
- **Region**: us-east-1 (configurable in code)
- **S3 Bucket**: images-bucket (update in `test.py` and `main.php`)
- **DynamoDB**: Provisioned throughput set to 5 read/write capacity units

### Security Considerations
- Passwords stored in plain text (implement hashing for production)
- Session management for user authentication
- AWS IAM permissions required for DynamoDB and S3 access

## 🚦 API Endpoints

The application uses PHP server-side processing with the following key operations:

- `POST /login.php` - User authentication
- `POST /register.php` - New user registration
- `POST /main.php` - Music search and subscription management
- `GET /logout.php` - Session termination

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Jonathan Theofilas**
- Email: JTheofilas24@gmail.com
- LinkedIn: [LinkedIn Profile](https://linkedin.com/in/jonathan-theofilas)
- GitHub: [GitHub Profile](https://github.com/JonathanTheofilas)

## 🙏 Acknowledgments

- AWS for providing comprehensive cloud services
- RMIT University for project guidance
- Open source community for various tools and libraries

---

⭐ If you found this project helpful, please give it a star!
