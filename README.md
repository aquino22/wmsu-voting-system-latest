🗳️ Voting System Web Application

A robust web-based voting system designed for academic institutions. It supports multiple election types, real-time result tracking, revoting for tie-breakers, and comprehensive administrative controls.

🚀 Features
🔐 Authentication & User Management
Secure login for voters and administrators
Role-based access (Admin, Voter)
Bulk voter import system (CSV/manual processing)
Email notifications with QR codes for verification
🗳️ Voting System
Supports multiple election scopes:
Central Elections
Local (College-based) Elections
External / ESU Elections
Dynamic ballot generation per election
One vote per voter enforcement
Precinct-based voter grouping
Election availability filtering (ongoing only)
🔄 Revoting System (Tie Resolution)
Automatic detection of tied candidates
Creation of revote scenarios
Controlled voter eligibility for revoting
Vote status lifecycle:
Not Voted
Voted
Revoted
Uses tied_candidates mapping for revote tracking
📊 Results & Analytics
Real-time vote counting
Candidate ranking system
Partylist vote distribution
Voter turnout statistics
Visual reports using Chart.js:
Bar charts
Pie charts
Winner summary per position
Export results to PDF
📁 File & Document Handling
PDF generation using TCPDF
Email delivery via PHPMailer
QR code generation for voter identification
🏗️ Tech Stack

Frontend

HTML, CSS, Bootstrap
JavaScript, jQuery
Chart.js

Backend

PHP (PDO)

Database

MySQL

Libraries

PHPMailer
TCPDF
QR Code Generator

⚙️ Installation
1. Clone the Repository
2. Setup Environment
Install XAMPP / LAMP / WAMP

Move project to:

htdocs/voting-system
3. Configure Database
Create a MySQL database
Import the provided .sqls file such as wmsu_voting_system and wmsu_voting_system_archived
Update config.php:
$host = 'localhost';
$db   = 'wmsu_voting_system';
$user = 'root';
$pass = '';
4. Install Dependencies
composer install
5. Run the Project
Start Apache and MySQL
Open in browser:
http://localhost/voting-system
