<?php
require_once __DIR__ . '/config.php';

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            // Create database directory if it doesn't exist
            $dbDir = dirname(DB_PATH);
            if (!file_exists($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            // Create SQLite connection
            $this->conn = new PDO('sqlite:' . DB_PATH);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Ensure UTF-8 encoding for Turkish characters
            $this->conn->exec("PRAGMA encoding = 'UTF-8';");

            // Enable foreign keys
            $this->conn->exec('PRAGMA foreign_keys = ON;');

            // Initialize database schema
            $this->initDatabase();
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    private function initDatabase()
    {
        $this->createTables();
        $this->insertDefaultData();
    }

    private function createTables()
    {
        $sql = "
        -- Users Table
        CREATE TABLE IF NOT EXISTS User (
            id TEXT PRIMARY KEY,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT NOT NULL,
            password TEXT NOT NULL,
            company_id TEXT NULL,
            balance INTEGER DEFAULT 800,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE SET NULL
        );

        -- Bus Company Table
        CREATE TABLE IF NOT EXISTS Bus_Company (
            id TEXT PRIMARY KEY,
            name TEXT UNIQUE NOT NULL,
            logo_path TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Trips Table
        CREATE TABLE IF NOT EXISTS Trips (
            id TEXT PRIMARY KEY,
            company_id TEXT NOT NULL,
            destination_city TEXT NOT NULL,
            arrival_time DATETIME NOT NULL,
            departure_time DATETIME NOT NULL,
            departure_city TEXT NOT NULL,
            price INTEGER NOT NULL,
            capacity INTEGER NOT NULL,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE CASCADE
        );

        -- Tickets Table
        CREATE TABLE IF NOT EXISTS Tickets (
            id TEXT PRIMARY KEY,
            trip_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            status TEXT DEFAULT 'active',
            total_price INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trip_id) REFERENCES Trips(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
        );

        -- Booked Seats Table
        CREATE TABLE IF NOT EXISTS Booked_Seats (
            id TEXT PRIMARY KEY,
            ticket_id TEXT NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES Tickets(id) ON DELETE CASCADE
        );

        -- Coupons Table
        CREATE TABLE IF NOT EXISTS Coupons (
            id TEXT PRIMARY KEY,
            code TEXT UNIQUE NOT NULL,
            discount REAL NOT NULL,
            usage_limit INTEGER NOT NULL,
            expire_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- User Coupons Table
        CREATE TABLE IF NOT EXISTS User_Coupons (
            id TEXT PRIMARY KEY,
            coupon_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (coupon_id) REFERENCES Coupons(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
        );

        -- Create indexes for better performance
        CREATE INDEX IF NOT EXISTS idx_trips_departure ON Trips(departure_city, destination_city, departure_time);
        CREATE INDEX IF NOT EXISTS idx_tickets_user ON Tickets(user_id);
        CREATE INDEX IF NOT EXISTS idx_tickets_trip ON Tickets(trip_id);
        CREATE INDEX IF NOT EXISTS idx_booked_seats_ticket ON Booked_Seats(ticket_id);
        ";

        $this->conn->exec($sql);
    }

    private function insertDefaultData()
    {
        try {
            // Check if admin exists
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM User WHERE role = ?");
            $stmt->execute([ROLE_ADMIN]);

            if ($stmt->fetchColumn() == 0) {
                // Insert default bus companies
                $companies = [
                    ['id' => $this->generateUUID(), 'name' => 'Metro Turizm', 'logo_path' => 'assets/images/metro.svg'],
                    ['id' => $this->generateUUID(), 'name' => 'Pamukkale', 'logo_path' => 'assets/images/pamukkale.svg'],
                    ['id' => $this->generateUUID(), 'name' => 'Kamil Koç', 'logo_path' => 'assets/images/kamilkoc.svg']
                ];

                foreach ($companies as $company) {
                    $stmt = $this->conn->prepare("INSERT INTO Bus_Company (id, name, logo_path) VALUES (?, ?, ?)");
                    $stmt->execute([$company['id'], $company['name'], $company['logo_path']]);
                }

                // Insert default admin
                $adminId = $this->generateUUID();
                $stmt = $this->conn->prepare("INSERT INTO User (id, full_name, email, role, password, balance) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $adminId,
                    'System Admin',
                    'admin@bussio.com',
                    ROLE_ADMIN,
                    password_hash('Admin123!', PASSWORD_BCRYPT),
                    0
                ]);

                // Insert company admin for Metro Turizm
                $companyAdminId = $this->generateUUID();
                $stmt = $this->conn->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, balance) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $companyAdminId,
                    'Metro Turizm Admin',
                    'firmadmin@metro.com',
                    ROLE_COMPANY_ADMIN,
                    password_hash('Firma123!', PASSWORD_BCRYPT),
                    $companies[0]['id'],
                    0
                ]);

                // Insert test user
                $userId = $this->generateUUID();
                $stmt = $this->conn->prepare("INSERT INTO User (id, full_name, email, role, password, balance) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    'Test User',
                    'user@example.com',
                    ROLE_USER,
                    password_hash('User123!', PASSWORD_BCRYPT),
                    DEFAULT_USER_BALANCE
                ]);

                // Insert sample trips
                $this->insertSampleTrips($companies[0]['id']);

                // Insert sample coupons
                $this->insertSampleCoupons();
            }
        } catch (PDOException $e) {
            error_log("Error inserting default data: " . $e->getMessage());
        }
    }

    private function insertSampleTrips($companyId)
    {
        $trips = [
            [
                'departure_city' => 'İstanbul',
                'destination_city' => 'Ankara',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days 09:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+2 days 15:00')),
                'price' => 350,
                'capacity' => 45
            ],
            [
                'departure_city' => 'İstanbul',
                'destination_city' => 'İzmir',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+3 days 10:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+3 days 18:00')),
                'price' => 400,
                'capacity' => 45
            ],
            [
                'departure_city' => 'Ankara',
                'destination_city' => 'Antalya',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+4 days 08:00')),
                'arrival_time' => date('Y-m-d H:i:s', strtotime('+4 days 16:00')),
                'price' => 450,
                'capacity' => 45
            ]
        ];

        foreach ($trips as $trip) {
            $stmt = $this->conn->prepare("
                INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->generateUUID(),
                $companyId,
                $trip['departure_city'],
                $trip['destination_city'],
                $trip['departure_time'],
                $trip['arrival_time'],
                $trip['price'],
                $trip['capacity']
            ]);
        }
    }

    private function insertSampleCoupons()
    {
        $coupons = [
            ['code' => 'WELCOME20', 'discount' => 20, 'usage_limit' => 100, 'expire_date' => date('Y-m-d H:i:s', strtotime('+30 days'))],
            ['code' => 'SUMMER50', 'discount' => 50, 'usage_limit' => 50, 'expire_date' => date('Y-m-d H:i:s', strtotime('+60 days'))]
        ];

        foreach ($coupons as $coupon) {
            $stmt = $this->conn->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $this->generateUUID(),
                $coupon['code'],
                $coupon['discount'],
                $coupon['usage_limit'],
                $coupon['expire_date']
            ]);
        }
    }

    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
