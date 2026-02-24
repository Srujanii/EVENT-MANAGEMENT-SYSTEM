-- Event Registration System Database
-- Updated per ER Diagram (includes Event_Sessions + payment_method)

CREATE DATABASE IF NOT EXISTS event_registration;
USE event_registration;

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS event_sessions;
DROP TABLE IF EXISTS participants;
DROP TABLE IF EXISTS events;

-- 1. Events
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time VARCHAR(100) NOT NULL,
    venue VARCHAR(255) NOT NULL,
    event_id_code VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_code (event_id_code),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Event_Sessions (new – from ER diagram)
CREATE TABLE event_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    time_start VARCHAR(50) NOT NULL,
    time_end VARCHAR(50) NOT NULL,
    room_number VARCHAR(100) NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Participants
CREATE TABLE participants (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    college VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Registrations
CREATE TABLE registrations (
    reg_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    event_id_code VARCHAR(50) NOT NULL,
    reg_id_code VARCHAR(50) UNIQUE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES participants(user_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_user (user_id),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Payments (added payment_method from ER diagram)
CREATE TABLE payments (
    pay_id INT AUTO_INCREMENT PRIMARY KEY,
    reg_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_id VARCHAR(255) UNIQUE NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'online',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reg_id) REFERENCES registrations(reg_id) ON DELETE CASCADE,
    INDEX idx_reg (reg_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample events (codes 101–110)
INSERT INTO events (name, date, time, venue, event_id_code) VALUES
('International Tech Summit',     '2026-03-22', '10:00 AM - 12:00 PM',  'Room A101',       '101'),
('AI & ML Conference',            '2026-04-05', '11:00 AM - 1:00 PM',   'Room B202',       '102'),
('Cloud Security Workshop',       '2026-04-18', '2:00 PM - 4:00 PM',    'Room C303',       '103'),
('Startup Innovation Expo',       '2026-05-10', '9:30 AM - 11:30 AM',   'Room D404',       '104'),
('Cybersecurity Awareness Program','2026-05-25','3:00 PM - 5:00 PM',    'Room E505',       '105'),
('Blockchain & Web3 Summit',      '2026-06-07', '10:00 AM - 12:30 PM',  'Room F606',       '106'),
('Data Science Hackathon',        '2026-06-14', '9:00 AM - 9:00 AM+1',  'Innovation Hub',  '107'),
('IoT & Embedded Systems Expo',   '2026-06-28', '11:00 AM - 2:00 PM',   'Lab G707',        '108'),
('Open Source Dev Day',           '2026-07-12', '10:00 AM - 3:00 PM',   'Room H808',       '109'),
('AR/VR Immersive Tech Show',     '2026-07-26', '1:00 PM - 5:00 PM',    'Auditorium Main', '110');

-- Sample sessions for each event
INSERT INTO event_sessions (event_id, session_name, time_start, time_end, room_number) VALUES
(1, 'Opening Keynote',           '10:00 AM', '10:45 AM', 'A101'),
(1, 'AI in Industry Panel',      '11:00 AM', '12:00 PM', 'A101'),
(2, 'Intro to ML',               '11:00 AM', '11:45 AM', 'B202'),
(2, 'Deep Learning Workshop',    '12:00 PM', '1:00 PM',  'B202'),
(3, 'Cloud Fundamentals',        '2:00 PM',  '3:00 PM',  'C303'),
(3, 'Hands-on Security Lab',     '3:00 PM',  '4:00 PM',  'C303'),
(4, 'Startup Pitches',           '9:30 AM',  '10:30 AM', 'D404'),
(4, 'Investor Networking',       '10:30 AM', '11:30 AM', 'D404'),
(5, 'Cyber Threats Overview',    '3:00 PM',  '4:00 PM',  'E505'),
(5, 'Live Demo & Q&A',           '4:00 PM',  '5:00 PM',  'E505');

SHOW TABLES;
SELECT e.name AS event, s.session_name, s.time_start, s.time_end, s.room_number
FROM event_sessions s JOIN events e ON s.event_id = e.event_id ORDER BY e.event_id, s.session_id;