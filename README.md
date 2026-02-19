🎉 Event Management System

📌 Project Overview
The Event Management System is a database-driven application designed to manage events, participants, venues, registrations, and administrators efficiently.
This project demonstrates the implementation of core DBMS concepts including entity relationships, normalization, primary and foreign keys, constraints, and SQL queries.

🎯 Objectives
To manage events and venues effectively
To allow participant registration for events
To track payment status of registrations
To maintain data integrity using database constraints
To implement ER Diagram to Relational Schema conversion

🗂️ Entities in the System
1️⃣ Event
EventID (Primary Key)
EventName
Date
Time
VenueID (Foreign Key)
Capacity

2️⃣ Participant
ParticipantID (Primary Key)
Name
Email
Phone

3️⃣ Venue
VenueID (Primary Key)
VenueName
Location
Capacity

4️⃣ Registration
RegistrationID (Primary Key)
EventID (Foreign Key)
ParticipantID (Foreign Key)
PaymentStatus

5️⃣ Administrator
AdminID (Primary Key)
Username
Password

🔗 Relationships
One Venue can host multiple Events
One Event can have multiple Registrations
One Participant can register for multiple Events
Registration acts as a bridge table (Many-to-Many relationship)

🛠️ Technologies Used
HTML (Frontend)
SQL (Database)
ER Diagram Modeling
MySQL / SQL Server (Database System)

🧠 DBMS Concepts Implemented
Primary Keys
Foreign Keys
One-to-Many Relationship
Many-to-Many Relationship
Normalization (Up to 3NF)
JOIN Queries
Aggregate Functions
Constraints (NOT NULL, UNIQUE)

▶️ How to Run
Install MySQL or any SQL DBMS.
Import the provided SQL file.
Execute table creation queries.
Insert sample data.
Run SELECT queries to test functionality.

🔐 Future Enhancements
Online payment integration
Email notifications
Admin dashboard
Seat availability tracking
Authentication system

👨‍💻 Author
Name:Sri Ramanarayana S N, Srujani V, Sooraj S
Course: DBMS
College: Christ University 
Year: 2026
