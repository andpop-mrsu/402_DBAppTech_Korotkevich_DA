<?php

namespace DmitriyKorotkevich\Hangman\Models;

use PDO;
use PDOException;

/**
 * Database connection and operations
 */
class Database
{
    private static ?PDO $connection = null;
    private static string $dbPath = __DIR__ . '/../../data/hangman.db';

    /**
     * Get database connection (singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::initialize();
        }
        
        return self::$connection;
    }

    /**
     * Initialize database connection and tables
     */
    private static function initialize(): void
    {
        try {
            // Create data directory if not exists
            $dataDir = dirname(self::$dbPath);
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }

            // Create PDO connection
            self::$connection = new PDO('sqlite:' . self::$dbPath);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Create tables
            self::createTables();
            
            // Initialize words if needed
            self::initializeWords();
            
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Create required tables
     */
    private static function createTables(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_name TEXT NOT NULL,
            secret_word TEXT NOT NULL,
            is_won INTEGER NOT NULL DEFAULT 0,
            played_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            attempt_number INTEGER NOT NULL,
            letter TEXT NOT NULL,
            is_correct INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS words (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            word TEXT UNIQUE NOT NULL
        );
        ";

        self::$connection->exec($sql);
    }

    /**
     * Initialize words table with default words
     */
    private static function initializeWords(): void
    {
        $defaultWords = [
            'python', 'golang', 'kotlin', 'ruby', 'java', 
            'swift', 'rust', 'scala', 'perl', 'basic',
            'clojure', 'erlang', 'haskell', 'pascal', 'julia'
        ];

        $checkStmt = self::$connection->query("SELECT COUNT(*) as count FROM words");
        $count = $checkStmt->fetch()['count'];

        if ($count == 0) {
            $stmt = self::$connection->prepare("INSERT OR IGNORE INTO words (word) VALUES (?)");
            
            foreach ($defaultWords as $word) {
                $stmt->execute([strtolower($word)]);
            }
        }
    }

    /**
     * Get random word from database
     */
    public static function getRandomWord(): string
    {
        $stmt = self::getConnection()->query("SELECT word FROM words ORDER BY RANDOM() LIMIT 1");
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new \RuntimeException('No words available in database');
        }
        
        return $result['word'];
    }

    /**
     * Add new word to database
     */
    public static function addWord(string $word): bool
    {
        $stmt = self::getConnection()->prepare("INSERT OR IGNORE INTO words (word) VALUES (?)");
        return $stmt->execute([strtolower($word)]);
    }

    /**
     * Get all words from database
     */
    public static function getAllWords(): array
    {
        $stmt = self::getConnection()->query("SELECT word FROM words ORDER BY word");
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Get database file path
     */
    public static function getDbPath(): string
    {
        return self::$dbPath;
    }
}
