-- For solo participants
CREATE TABLE solo_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) UNIQUE,
    email VARCHAR(100),
    participant_name VARCHAR(100),
    contact_number VARCHAR(20),
    class VARCHAR(50),
    institution VARCHAR(100),
    event_name VARCHAR(100),
    category VARCHAR(50),
    bkash_transaction_id VARCHAR(50),
    event_date DATE,
    event_time TIME,
    mail_sent BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- For group participants
CREATE TABLE group_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) UNIQUE,
    email VARCHAR(100),
    team_name VARCHAR(100),
    team_member_1 VARCHAR(100),
    institution_1 VARCHAR(100),
    contact_1 VARCHAR(20),
    team_member_2 VARCHAR(100),
    institution_2 VARCHAR(100),
    contact_2 VARCHAR(20),
    team_member_3 VARCHAR(100),
    institution_3 VARCHAR(100),
    contact_3 VARCHAR(20),
    team_member_4 VARCHAR(100),
    institution_4 VARCHAR(100),
    contact_4 VARCHAR(20),
    team_member_5 VARCHAR(100),
    institution_5 VARCHAR(100),
    contact_5 VARCHAR(20),
    event_name VARCHAR(100),
    category VARCHAR(50),
    bkash_transaction_id VARCHAR(50),
    event_date DATE,
    event_time TIME,
    mail_sent BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- For transactions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    received_amount DECIMAL(10,2),
    bkash_number VARCHAR(20),
    fee_amount DECIMAL(10,2),
    current_balance DECIMAL(10,2),
    transaction_id VARCHAR(50) UNIQUE,
    transaction_time DATETIME,
    remarks ENUM('Not Verified', 'Verified') DEFAULT 'Not Verified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
