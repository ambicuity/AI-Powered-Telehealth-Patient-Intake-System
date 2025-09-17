CREATE DATABASE IF NOT EXISTS telehealth_db;
CREATE USER IF NOT EXISTS 'telehealth_user'@'%' IDENTIFIED BY 'telehealth_password';
GRANT ALL PRIVILEGES ON telehealth_db.* TO 'telehealth_user'@'%';
FLUSH PRIVILEGES;