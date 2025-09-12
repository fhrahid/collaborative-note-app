<?php
// Local development database configuration using SQLite
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../data/note_app.db');

// Database connection class for local development
class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Create data directory if it doesn't exist
            $dataDir = dirname(DB_PATH);
            if (!file_exists($dataDir)) {
                mkdir($dataDir, 0755, true);
            }

            $this->conn = new PDO("sqlite:" . DB_PATH);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Create tables if they don't exist
            $this->createTables();
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    private function createTables() {
        // Create users table
        $usersTable = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Create notes table with sharing columns
        $notesTable = "
            CREATE TABLE IF NOT EXISTS notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                is_public INTEGER DEFAULT 0,
                share_token VARCHAR(255) UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";

        // Create shared_notes table for user-specific sharing
        $sharedNotesTable = "
            CREATE TABLE IF NOT EXISTS shared_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                note_id INTEGER NOT NULL,
                shared_by_user_id INTEGER NOT NULL,
                shared_with_user_id INTEGER NOT NULL,
                permission VARCHAR(20) DEFAULT 'read',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
                FOREIGN KEY (shared_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(note_id, shared_with_user_id)
            )
        ";

        // Create attachments table
        $attachmentsTable = "
            CREATE TABLE IF NOT EXISTS attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                note_id INTEGER NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                file_size INTEGER NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
            )
        ";

        // Create collaborators table
        $collaboratorsTable = "
            CREATE TABLE IF NOT EXISTS collaborators (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                note_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                added_by_user_id INTEGER NOT NULL,
                permission_level VARCHAR(20) DEFAULT 'read',
                added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (added_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(note_id, user_id)
            )
        ";

        // Execute table creation
        $this->conn->exec($usersTable);
        $this->conn->exec($notesTable);
        $this->conn->exec($sharedNotesTable);
        $this->conn->exec($attachmentsTable);
        $this->conn->exec($collaboratorsTable);

        // Create indexes
        $userIndex = "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)";
        $emailIndex = "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)";
        $notesUserIndex = "CREATE INDEX IF NOT EXISTS idx_notes_user_id ON notes(user_id)";
        $notesDateIndex = "CREATE INDEX IF NOT EXISTS idx_notes_created_at ON notes(created_at)";
        $notesTokenIndex = "CREATE INDEX IF NOT EXISTS idx_notes_share_token ON notes(share_token)";
        $sharedNotesIndex = "CREATE INDEX IF NOT EXISTS idx_shared_notes_user ON shared_notes(shared_with_user_id)";
        $sharedNotesNoteIndex = "CREATE INDEX IF NOT EXISTS idx_shared_notes_note ON shared_notes(note_id)";
        $attachmentsNoteIndex = "CREATE INDEX IF NOT EXISTS idx_attachments_note_id ON attachments(note_id)";
        $collaboratorsNoteIndex = "CREATE INDEX IF NOT EXISTS idx_collaborators_note_id ON collaborators(note_id)";
        $collaboratorsUserIndex = "CREATE INDEX IF NOT EXISTS idx_collaborators_user_id ON collaborators(user_id)";

        $this->conn->exec($userIndex);
        $this->conn->exec($emailIndex);
        $this->conn->exec($notesUserIndex);
        $this->conn->exec($notesDateIndex);
        $this->conn->exec($notesTokenIndex);
        $this->conn->exec($sharedNotesIndex);
        $this->conn->exec($sharedNotesNoteIndex);
        $this->conn->exec($attachmentsNoteIndex);
        $this->conn->exec($collaboratorsNoteIndex);
        $this->conn->exec($collaboratorsUserIndex);
    }
}

// Create global database connection
$database = new Database();
$db = $database->getConnection();
?>