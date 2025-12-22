<?php

namespace DmitriyKorotkevich\Hangman\Models;

use RedBeanPHP\R;

/**
 * Database connection using RedBeanPHP ORM
 */
class Database
{
    private static bool $initialized = false;
    private static string $dbPath = __DIR__ . '/../../data/hangman.db';

    /**
     * Initialize database connection
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Create data directory if not exists
        $dataDir = dirname(self::$dbPath);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // Setup RedBeanPHP
        R::setup('sqlite:' . self::$dbPath);
        
        // Disable facade to allow RedBeanPHP 5.x compatibility
        if (class_exists('RedBeanPHP\Facade')) {
            R::useWriterCache(true);
        }
        
        // Don't freeze in development, freeze in production
        R::freeze(false);
        
        // Create tables if they don't exist
        self::createTables();
        
        // Initialize words if needed
        self::initializeWords();
        
        self::$initialized = true;
    }

    /**
     * Create required tables using RedBeanPHP
     */
    private static function createTables(): void
    {
        // Create games table
        if (!R::testConnection('games')) {
            $game = R::dispense('game');
            $game->playerName = 'Player';
            $game->secretWord = 'test';
            $game->isWon = false;
            $game->playedAt = date('Y-m-d H:i:s');
            R::store($game);
            R::trash($game); // Remove test record
        }

        // Create attempts table
        if (!R::testConnection('attempts')) {
            $attempt = R::dispense('attempt');
            $attempt->gameId = 1;
            $attempt->attemptNumber = 1;
            $attempt->letter = 'a';
            $attempt->isCorrect = true;
            R::store($attempt);
            R::trash($attempt); // Remove test record
        }

        // Create words table
        if (!R::testConnection('words')) {
            $word = R::dispense('word');
            $word->word = 'test';
            R::store($word);
            R::trash($word); // Remove test record
        }
    }

    /**
     * Initialize words table with default words
     */
    private static function initializeWords(): void
    {
        $count = R::count('word');
        
        if ($count == 0) {
            $defaultWords = [
                'python', 'golang', 'kotlin', 'ruby', 'java', 
                'swift', 'rust', 'scala', 'perl', 'basic',
                'clojure', 'erlang', 'haskell', 'pascal', 'julia'
            ];

            foreach ($defaultWords as $word) {
                $wordBean = R::dispense('word');
                $wordBean->word = strtolower($word);
                R::store($wordBean);
            }
        }
    }

    /**
     * Get random word from database using RedBeanPHP
     */
    public static function getRandomWord(): string
    {
        self::initialize();
        
        $word = R::findOne('word', ' ORDER BY RANDOM() ');
        
        if (!$word) {
            throw new \RuntimeException('No words available in database');
        }
        
        return $word->word;
    }

    /**
     * Add new word to database
     */
    public static function addWord(string $word): bool
    {
        self::initialize();
        
        $existing = R::findOne('word', ' word = ? ', [strtolower($word)]);
        
        if (!$existing) {
            $wordBean = R::dispense('word');
            $wordBean->word = strtolower($word);
            R::store($wordBean);
            return true;
        }
        
        return false;
    }

    /**
     * Get all words from database
     */
    public static function getAllWords(): array
    {
        self::initialize();
        
        $words = R::findAll('word', ' ORDER BY word ');
        $result = [];
        
        foreach ($words as $word) {
            $result[] = $word->word;
        }
        
        return $result;
    }

    /**
     * Get database file path
     */
    public static function getDbPath(): string
    {
        return self::$dbPath;
    }

    /**
     * Close database connection
     */
    public static function close(): void
    {
        if (self::$initialized) {
            R::close();
            self::$initialized = false;
        }
    }
}
