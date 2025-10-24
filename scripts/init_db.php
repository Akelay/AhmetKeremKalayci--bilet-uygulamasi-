<?php
declare(strict_types=1);

require __DIR__ . '/../app/Models/DB.php';
$config = require __DIR__ . '/../config/config.php';

$dbFile = $config['db_path'];
$dir = dirname($dbFile);

if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
if (!file_exists($dbFile)) {
    touch($dbFile);
    @chmod($dbFile, 0666);
}

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON'); // FK'lar çalışsın

$sql = <<<SQL
-- =========================
-- Bus_Company
-- =========================
CREATE TABLE IF NOT EXISTS Bus_Company (
  id         TEXT PRIMARY KEY,          
  name       TEXT NOT NULL UNIQUE,     
  logo_path  TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- User
-- =========================
CREATE TABLE IF NOT EXISTS User (
  id          TEXT PRIMARY KEY,         
  full_name   TEXT NOT NULL,
  email       TEXT NOT NULL UNIQUE,      
  role        TEXT NOT NULL,             
  password    TEXT NOT NULL,            
  company_id  TEXT,                     
  balance     INTEGER DEFAULT 800,      
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- =========================
-- Trips
-- =========================
CREATE TABLE IF NOT EXISTS Trips (
  id               TEXT PRIMARY KEY,    
  company_id       TEXT NOT NULL,
  destination_city TEXT NOT NULL,
  arrival_time     DATETIME NOT NULL,
  departure_time   DATETIME NOT NULL,
  departure_city   TEXT NOT NULL,
  price            INTEGER NOT NULL,
  capacity         INTEGER NOT NULL,
  created_date     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- =========================
-- Tickets
-- =========================
CREATE TABLE IF NOT EXISTS Tickets (
  id               TEXT PRIMARY KEY,     
  trip_id          TEXT NOT NULL,
  user_id          TEXT NOT NULL,
  status           TEXT DEFAULT 'active',-- (active,canceled,expired) 
  total_price      INTEGER NOT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (trip_id) REFERENCES Trips(id),
  FOREIGN KEY (user_id) REFERENCES User(id)
);

-- =========================
-- Booked_Seats
-- =========================
CREATE TABLE IF NOT EXISTS Booked_Seats (
  id          TEXT PRIMARY KEY,         
  ticket_id   TEXT NOT NULL,
  seat_number INTEGER NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES Tickets(id)
);

-- =========================
-- Coupons
-- =========================
CREATE TABLE IF NOT EXISTS Coupons (
  id          TEXT PRIMARY KEY,          
  code        TEXT NOT NULL UNIQUE,      
  discount    REAL NOT NULL,
  usage_limit INTEGER NOT NULL,
  company_id  TEXT,
  expire_date DATETIME NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- =========================
-- User_Coupons
-- =========================
CREATE TABLE IF NOT EXISTS User_Coupons (
  id         TEXT PRIMARY KEY,           
  coupon_id  TEXT NOT NULL,
  user_id    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (coupon_id) REFERENCES Coupons(id),
  FOREIGN KEY (user_id)   REFERENCES User(id)
);
SQL;

$pdo->exec($sql);

echo "Veritabanı oluşturuldu.\n";
